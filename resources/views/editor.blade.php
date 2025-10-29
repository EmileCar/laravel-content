<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Content Editor - {{ $page->display_name ?? 'New Page' }}</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="{{ asset('vendor/carone-content/css/content-editor.css') }}" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 bg-light sidebar">
                <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
                    <h5 class="mb-0">Content Editor</h5>
                    <button class="btn btn-sm btn-outline-secondary" onclick="togglePreview()">
                        <i class="fas fa-eye"></i> Preview
                    </button>
                </div>
                
                <!-- Page Info -->
                <div class="p-3 border-bottom">
                    <div class="mb-3">
                        <label for="pageName" class="form-label">Page Name</label>
                        <input type="text" class="form-control" id="pageName" value="{{ $page->name ?? '' }}" {{ isset($page) ? 'readonly' : '' }}>
                    </div>
                    <div class="mb-3">
                        <label for="displayName" class="form-label">Display Name</label>
                        <input type="text" class="form-control" id="displayName" value="{{ $page->display_name ?? '' }}">
                    </div>
                    <div class="mb-3">
                        <label for="pageType" class="form-label">Type</label>
                        <select class="form-control" id="pageType">
                            <option value="page" {{ ($page->type ?? 'page') === 'page' ? 'selected' : '' }}>Page</option>
                            <option value="fragment" {{ ($page->type ?? '') === 'fragment' ? 'selected' : '' }}>Fragment</option>
                            <option value="block" {{ ($page->type ?? '') === 'block' ? 'selected' : '' }}>Block</option>
                            <option value="landing" {{ ($page->type ?? '') === 'landing' ? 'selected' : '' }}>Landing</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="pageLocale" class="form-label">Locale</label>
                        <input type="text" class="form-control" id="pageLocale" value="{{ $page->locale ?? 'en' }}" placeholder="en">
                    </div>
                </div>

                <!-- Block Library -->
                <div class="p-3">
                    <h6>Add Block</h6>
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary btn-sm" onclick="addBlock('hero')">
                            <i class="fas fa-star"></i> Hero
                        </button>
                        <button class="btn btn-outline-primary btn-sm" onclick="addBlock('text')">
                            <i class="fas fa-paragraph"></i> Text
                        </button>
                        <button class="btn btn-outline-primary btn-sm" onclick="addBlock('feature_grid')">
                            <i class="fas fa-th"></i> Feature Grid
                        </button>
                        <button class="btn btn-outline-primary btn-sm" onclick="addBlock('image')">
                            <i class="fas fa-image"></i> Image
                        </button>
                        <button class="btn btn-outline-primary btn-sm" onclick="addBlock('footer')">
                            <i class="fas fa-copyright"></i> Footer
                        </button>
                        <hr>
                        <small class="text-muted">Custom Blocks</small>
                        <button class="btn btn-outline-success btn-sm" onclick="addBlock('custom_block')">
                            <i class="fas fa-magic"></i> Custom Block
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="addBlock('custom')">
                            <i class="fas fa-code"></i> Raw JSON
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                    <div>
                        <h4 class="mb-0" id="pageTitle">{{ $page->display_name ?? 'New Page' }}</h4>
                        <small class="text-muted">Version: {{ $page->version ?? 1 }}</small>
                    </div>
                    <div>
                        <button class="btn btn-outline-secondary me-2" onclick="previewChanges()">
                            <i class="fas fa-eye"></i> Preview
                        </button>
                        <button class="btn btn-success" onclick="savePage()">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </div>

                <!-- Editor Area -->
                <div class="editor-container">
                    <div id="blocksContainer" class="blocks-container">
                        <!-- Blocks will be dynamically added here -->
                    </div>
                    
                    <div class="add-block-prompt text-center p-4" id="addBlockPrompt">
                        <p class="text-muted">Click "Add Block" in the sidebar to start building your page</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Block Template (Hidden) -->
    <template id="blockTemplate">
        <div class="block-editor" data-block-id="">
            <div class="block-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="block-type-label"></span>
                        <input type="text" class="block-id-input" placeholder="Block ID">
                    </div>
                    <div>
                        <button class="btn btn-sm btn-outline-secondary me-1" onclick="moveBlockUp(this)">
                            <i class="fas fa-arrow-up"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary me-1" onclick="moveBlockDown(this)">
                            <i class="fas fa-arrow-down"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="removeBlock(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="block-content">
                <!-- Block-specific content will be added here -->
            </div>
        </div>
    </template>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="{{ asset('vendor/carone-content/js/content-editor.js') }}"></script>
    
    <script>
        // Initialize the editor with existing data
        document.addEventListener('DOMContentLoaded', function() {
            @if(isset($page) && $page->value)
                const pageData = @json($page->value);
                initializeEditor(pageData, {{ $page->id ?? 'null' }}, {{ $page->version ?? 1 }});
            @else
                initializeEditor(null, null, 1);
            @endif
        });
    </script>
</body>
</html>