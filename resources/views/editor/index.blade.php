<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Content Editor</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f7fa;
            color: #2d3748;
        }

        .header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1a202c;
        }

        .container {
            display: flex;
            height: calc(100vh - 73px);
        }

        .sidebar {
            width: 280px;
            background: white;
            border-right: 1px solid #e2e8f0;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 1.25rem;
            border-bottom: 1px solid #e2e8f0;
            background: #f7fafc;
        }

        .sidebar-header h2 {
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #718096;
        }

        .page-list {
            list-style: none;
        }

        .page-item {
            padding: 0.875rem 1.25rem;
            cursor: pointer;
            transition: all 0.2s;
            border-bottom: 1px solid #f7fafc;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .page-item:hover {
            background: #f7fafc;
        }

        .page-item.active {
            background: #ebf8ff;
            border-left: 3px solid #3182ce;
            color: #2c5282;
            font-weight: 500;
        }

        .page-name {
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .content-count {
            background: #edf2f7;
            color: #4a5568;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-weight: 500;
        }

        .page-item.active .content-count {
            background: #bee3f8;
            color: #2c5282;
        }

        .main-content {
            flex: 1;
            overflow-y: auto;
            padding: 2rem;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #718096;
        }

        .empty-state svg {
            width: 64px;
            height: 64px;
            margin: 0 auto 1rem;
            opacity: 0.5;
        }

        .content-header {
            margin-bottom: 2rem;
        }

        .content-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .content-header p {
            color: #718096;
        }

        .add-content-btn {
            background: #3182ce;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
            margin-bottom: 1.5rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .add-content-btn:hover {
            background: #2c5282;
        }

        .content-grid {
            display: grid;
            gap: 1rem;
        }

        .content-card {
            background: white;
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            transition: box-shadow 0.2s;
        }

        .content-card:hover {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .content-card-header {
            padding: 1rem 1.25rem;
            background: #f7fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .content-type-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .content-type-badge.text {
            background: #c6f6d5;
            color: #22543d;
        }

        .content-type-badge.image {
            background: #fed7d7;
            color: #742a2a;
        }

        .content-type-badge.file {
            background: #feebc8;
            color: #7c2d12;
        }

        .content-card-body {
            padding: 1.25rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.875rem;
            color: #4a5568;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: #3182ce;
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .btn-group {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid transparent;
        }

        .btn-primary {
            background: #3182ce;
            color: white;
        }

        .btn-primary:hover {
            background: #2c5282;
        }

        .btn-danger {
            background: #e53e3e;
            color: white;
        }

        .btn-danger:hover {
            background: #c53030;
        }

        .btn-secondary {
            background: #edf2f7;
            color: #4a5568;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 0.5rem;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .modal-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }

        .alert-info {
            background: #ebf8ff;
            color: #2c5282;
            border: 1px solid #bee3f8;
        }

        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }

        .alert-error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: #718096;
        }

        .spinner {
            border: 3px solid #e2e8f0;
            border-top: 3px solid #3182ce;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📝 Content Editor</h1>
    </div>

    <div class="container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Pages</h2>
            </div>
            <ul class="page-list" id="pageList">
                @forelse($pages as $page)
                    <li class="page-item" data-page="{{ $page }}" onclick="loadPage('{{ $page }}')">
                        <span class="page-name">{{ $page }}</span>
                        <span class="content-count" id="count-{{ Str::slug($page) }}">0</span>
                    </li>
                @empty
                    <li style="padding: 2rem 1.25rem; text-align: center; color: #718096;">
                        No pages found
                    </li>
                @endforelse
            </ul>
        </div>

        <div class="main-content" id="mainContent">
            <div class="empty-state">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 style="margin-bottom: 0.5rem;">Select a page to edit content</h3>
                <p>Choose a page from the sidebar to view and edit its content</p>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal" id="contentModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add Content</h3>
            </div>
            <div class="modal-body">
                <div class="alert alert-info" id="codeReminderAlert" style="display: none;">
                    <strong>⚠️ Code Update Required</strong><br>
                    After adding new content, remember to add the corresponding component to your view:
                    <code style="display: block; margin-top: 0.5rem; padding: 0.5rem; background: white; border-radius: 0.25rem;">
                        &lt;x-editable-[type] element="[element-id]" /&gt;
                    </code>
                </div>

                <form id="contentForm">
                    <input type="hidden" id="contentId" name="id">
                    <input type="hidden" id="pageId" name="page_id">

                    <div class="form-group">
                        <label for="elementId">Element ID *</label>
                        <input type="text" class="form-control" id="elementId" name="element_id" required placeholder="e.g., hero-title">
                    </div>

                    <div class="form-group">
                        <label for="contentType">Content Type *</label>
                        <select class="form-control" id="contentType" name="type" required>
                            <option value="text">Text</option>
                            <option value="image">Image</option>
                            <option value="file">File</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="contentValue">Value</label>
                        <textarea class="form-control" id="contentValue" name="value" placeholder="Enter content value"></textarea>
                        <small style="color: #718096; font-size: 0.75rem; display: block; margin-top: 0.25rem;">
                            For images and files, enter the path or URL
                        </small>
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">Save Content</button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let currentPage = null;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        function loadPage(pageId) {
            currentPage = pageId;
            
            // Update active state
            document.querySelectorAll('.page-item').forEach(item => {
                item.classList.remove('active');
            });
            event.target.closest('.page-item').classList.add('active');

            // Show loading
            document.getElementById('mainContent').innerHTML = `
                <div class="loading">
                    <div class="spinner"></div>
                    <p>Loading content...</p>
                </div>
            `;

            // Fetch page content
            fetch(`{{ config('content.route_prefix', 'admin/content') }}/page/${pageId}`, {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                displayPageContent(data);
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('mainContent').innerHTML = `
                    <div class="alert alert-error">
                        Error loading content. Please try again.
                    </div>
                `;
            });
        }

        function displayPageContent(data) {
            const contents = data.contents;
            
            // Update count
            const countElement = document.getElementById(`count-${data.page_id.replace(/\./g, '-')}`);
            if (countElement) {
                countElement.textContent = contents.length;
            }

            let html = `
                <div class="content-header">
                    <h2>${data.page_id}</h2>
                    <p>${contents.length} content item(s)</p>
                </div>
                <button class="add-content-btn" onclick="openAddModal()">
                    ➕ Add New Content
                </button>
                <div class="content-grid">
            `;

            if (contents.length === 0) {
                html += `
                    <div class="empty-state">
                        <p>No content found for this page. Click "Add New Content" to get started.</p>
                    </div>
                `;
            } else {
                contents.forEach(content => {
                    html += createContentCard(content);
                });
            }

            html += '</div>';
            document.getElementById('mainContent').innerHTML = html;
        }

        function createContentCard(content) {
            const value = content.value || '';
            const displayValue = content.type === 'text' && value.length > 200 
                ? value.substring(0, 200) + '...' 
                : value;

            return `
                <div class="content-card">
                    <div class="content-card-header">
                        <div>
                            <strong>${content.element_id}</strong>
                            <span class="content-type-badge ${content.type}">${content.type}</span>
                        </div>
                    </div>
                    <div class="content-card-body">
                        <div class="form-group">
                            <label>Value</label>
                            <textarea class="form-control" id="value-${content.id}" onchange="updateContent(${content.id})">${value}</textarea>
                        </div>
                        <div class="btn-group">
                            <button class="btn btn-primary" onclick="saveContentValue(${content.id})">Save</button>
                            <button class="btn btn-danger" onclick="deleteContent(${content.id})">Delete</button>
                        </div>
                    </div>
                </div>
            `;
        }

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Content';
            document.getElementById('contentForm').reset();
            document.getElementById('contentId').value = '';
            document.getElementById('pageId').value = currentPage;
            document.getElementById('codeReminderAlert').style.display = 'block';
            document.getElementById('contentModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('contentModal').classList.remove('active');
        }

        document.getElementById('contentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = {
                page_id: document.getElementById('pageId').value,
                element_id: document.getElementById('elementId').value,
                type: document.getElementById('contentType').value,
                value: document.getElementById('contentValue').value
            };

            fetch(`{{ config('content.route_prefix', 'admin/content') }}/content`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal();
                    loadPage(currentPage);
                } else {
                    alert('Error saving content');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving content');
            });
        });

        function saveContentValue(contentId) {
            const value = document.getElementById(`value-${contentId}`).value;
            const pageItem = document.querySelector('.page-item.active');
            const pageId = pageItem ? pageItem.dataset.page : currentPage;

            // Find the content to get element_id and type
            fetch(`{{ config('content.route_prefix', 'admin/content') }}/page/${pageId}`, {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                const content = data.contents.find(c => c.id === contentId);
                if (content) {
                    return fetch(`{{ config('content.route_prefix', 'admin/content') }}/content`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            page_id: pageId,
                            element_id: content.element_id,
                            type: content.type,
                            value: value
                        })
                    });
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success feedback
                    const btn = event.target;
                    const originalText = btn.textContent;
                    btn.textContent = '✓ Saved';
                    btn.style.background = '#38a169';
                    setTimeout(() => {
                        btn.textContent = originalText;
                        btn.style.background = '';
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving content');
            });
        }

        function deleteContent(contentId) {
            if (!confirm('Are you sure you want to delete this content?')) {
                return;
            }

            fetch(`{{ config('content.route_prefix', 'admin/content') }}/content/${contentId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadPage(currentPage);
                } else {
                    alert('Error deleting content');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting content');
            });
        }

        // Close modal on outside click
        document.getElementById('contentModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
