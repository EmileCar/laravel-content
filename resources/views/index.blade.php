<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Content Management</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
                    <h2>Content Management</h2>
                    <a href="{{ route('content.editor') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create New Page
                    </a>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Pages</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Display Name</th>
                                                <th>Type</th>
                                                <th>Locale</th>
                                                <th>Version</th>
                                                <th>Updated</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="pagesTableBody">
                                            <tr>
                                                <td colspan="7" class="text-center">
                                                    <div class="spinner-border" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <nav aria-label="Pages pagination" id="pagination">
                                    <!-- Pagination will be inserted here -->
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadPages();
        });

        async function loadPages(page = 1) {
            try {
                const response = await fetch(`/admin/content/pages?page=${page}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to load pages');
                }

                const data = await response.json();
                renderPages(data.data);
                renderPagination(data);
            } catch (error) {
                console.error('Error loading pages:', error);
                document.getElementById('pagesTableBody').innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center text-danger">
                            Failed to load pages. Please try again.
                        </td>
                    </tr>
                `;
            }
        }

        function renderPages(pages) {
            const tbody = document.getElementById('pagesTableBody');
            
            if (pages.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center text-muted">
                            No pages found. <a href="{{ route('content.editor') }}">Create your first page</a>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = pages.map(page => `
                <tr>
                    <td><code>${escapeHtml(page.name)}</code></td>
                    <td>${escapeHtml(page.display_name)}</td>
                    <td><span class="badge bg-secondary">${escapeHtml(page.type)}</span></td>
                    <td>${page.locale ? `<span class="badge bg-info">${escapeHtml(page.locale)}</span>` : '-'}</td>
                    <td>${page.version}</td>
                    <td>${formatDate(page.updated_at)}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="/admin/content/editor/${page.name}" class="btn btn-outline-primary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button class="btn btn-outline-info" onclick="previewPage('${page.name}')" title="Preview">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="deletePage('${page.name}')" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        function renderPagination(data) {
            const pagination = document.getElementById('pagination');
            
            if (data.last_page <= 1) {
                pagination.innerHTML = '';
                return;
            }

            let paginationHtml = '<ul class="pagination justify-content-center">';
            
            // Previous page
            if (data.current_page > 1) {
                paginationHtml += `
                    <li class="page-item">
                        <a class="page-link" href="#" onclick="loadPages(${data.current_page - 1})">Previous</a>
                    </li>
                `;
            }

            // Page numbers
            for (let i = Math.max(1, data.current_page - 2); i <= Math.min(data.last_page, data.current_page + 2); i++) {
                paginationHtml += `
                    <li class="page-item ${i === data.current_page ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="loadPages(${i})">${i}</a>
                    </li>
                `;
            }

            // Next page
            if (data.current_page < data.last_page) {
                paginationHtml += `
                    <li class="page-item">
                        <a class="page-link" href="#" onclick="loadPages(${data.current_page + 1})">Next</a>
                    </li>
                `;
            }

            paginationHtml += '</ul>';
            pagination.innerHTML = paginationHtml;
        }

        async function deletePage(pageName) {
            if (!confirm(`Are you sure you want to delete the page "${pageName}"? This action cannot be undone.`)) {
                return;
            }

            try {
                const response = await fetch(`/admin/content/pages/${pageName}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    alert('Page deleted successfully');
                    loadPages();
                } else {
                    const error = await response.json();
                    throw new Error(error.error || 'Delete failed');
                }
            } catch (error) {
                alert('Failed to delete page: ' + error.message);
            }
        }

        function previewPage(pageName) {
            // This would open a preview of the page
            // Implementation depends on your frontend structure
            alert(`Preview functionality for "${pageName}" would be implemented based on your site structure`);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        }
    </script>
</body>
</html>