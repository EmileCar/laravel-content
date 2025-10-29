// Content Editor JavaScript
class ContentEditor {
    constructor() {
        this.blocks = [];
        this.pageId = null;
        this.currentVersion = 1;
        this.isDirty = false;
        this.apiBaseUrl = '/admin/content';
        this.setupCSRF();
        this.bindEvents();
    }

    setupCSRF() {
        // Set up CSRF token for all AJAX requests
        const token = document.querySelector('meta[name="csrf-token"]');
        if (token) {
            this.csrfToken = token.getAttribute('content');
        }
    }

    bindEvents() {
        // Auto-save on input changes
        document.addEventListener('input', (e) => {
            if (e.target.closest('.block-editor')) {
                this.isDirty = true;
                this.updateBlockData(e.target);
            }
        });

        // Page info changes
        ['pageName', 'displayName', 'pageType', 'pageLocale'].forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener('input', () => {
                    this.isDirty = true;
                    this.updatePageTitle();
                });
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                this.savePage();
            }
        });

        // Warn about unsaved changes
        window.addEventListener('beforeunload', (e) => {
            if (this.isDirty) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    }

    initializeEditor(pageData, pageId, version) {
        this.pageId = pageId;
        this.currentVersion = version;
        
        if (pageData && pageData.blocks) {
            pageData.blocks.forEach(block => {
                this.addExistingBlock(block);
            });
            document.getElementById('addBlockPrompt').style.display = 'none';
        }
    }

    addBlock(type) {
        const blockId = this.generateBlockId(type);
        const blockData = this.getDefaultBlockData(type);
        
        const block = {
            id: blockId,
            type: type,
            data: blockData
        };

        this.addExistingBlock(block);
        this.isDirty = true;
        document.getElementById('addBlockPrompt').style.display = 'none';
    }

    addExistingBlock(block) {
        const container = document.getElementById('blocksContainer');
        const blockElement = this.createBlockElement(block);
        
        blockElement.classList.add('adding');
        container.appendChild(blockElement);
        
        // Remove animation class after animation completes
        setTimeout(() => {
            blockElement.classList.remove('adding');
        }, 300);

        this.blocks.push(block);
    }

    createBlockElement(block) {
        const template = document.getElementById('blockTemplate');
        const blockElement = template.content.cloneNode(true);
        const blockDiv = blockElement.querySelector('.block-editor');
        
        blockDiv.setAttribute('data-block-id', block.id);
        blockDiv.querySelector('.block-type-label').textContent = block.type;
        blockDiv.querySelector('.block-id-input').value = block.id;
        
        const contentDiv = blockDiv.querySelector('.block-content');
        contentDiv.innerHTML = this.generateBlockFields(block);
        
        return blockDiv;
    }

    generateBlockFields(block) {
        switch (block.type) {
            case 'hero':
                return this.generateHeroFields(block.data);
            case 'text':
                return this.generateTextFields(block.data);
            case 'feature_grid':
                return this.generateFeatureGridFields(block.data);
            case 'image':
                return this.generateImageFields(block.data);
            case 'footer':
                return this.generateFooterFields(block.data);
            case 'custom':
                return this.generateCustomFields(block.data);
            default:
                return this.generateCustomFields(block.data);
        }
    }

    generateHeroFields(data) {
        return `
            <div class="field-group">
                <label class="field-label">Heading</label>
                <input type="text" class="field-input" data-field="heading" value="${data.heading || ''}">
            </div>
            <div class="field-group">
                <label class="field-label">Subheading</label>
                <textarea class="field-input field-textarea" data-field="subheading">${data.subheading || ''}</textarea>
            </div>
            <div class="field-group">
                <label class="field-label">Background Image</label>
                <input type="text" class="field-input field-url-input" data-field="background_image" value="${data.background_image || ''}" placeholder="media://image.jpg">
            </div>
            <div class="field-group">
                <label class="field-label">Call to Action</label>
                <div class="row">
                    <div class="col-md-6">
                        <input type="text" class="field-input" data-field="cta.text" value="${data.cta?.text || ''}" placeholder="Button Text">
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="field-input field-url-input" data-field="cta.url" value="${data.cta?.url || ''}" placeholder="/link">
                    </div>
                </div>
            </div>
            ${this.generateBlockPreview(data)}
        `;
    }

    generateTextFields(data) {
        return `
            <div class="field-group">
                <label class="field-label">Heading</label>
                <input type="text" class="field-input" data-field="heading" value="${data.heading || ''}">
            </div>
            <div class="field-group">
                <label class="field-label">Content</label>
                <textarea class="field-input field-textarea" data-field="content" rows="6">${data.content || ''}</textarea>
            </div>
            ${this.generateBlockPreview(data)}
        `;
    }

    generateFeatureGridFields(data) {
        const features = data.features || [];
        let featuresHtml = '';
        
        features.forEach((feature, index) => {
            featuresHtml += `
                <div class="feature-item" data-index="${index}">
                    <div class="d-flex justify-content-between">
                        <strong>Feature ${index + 1}</strong>
                        <button type="button" class="btn btn-danger btn-sm btn-remove-feature" onclick="removeFeature(this)">Remove</button>
                    </div>
                    <div class="mb-2">
                        <input type="text" class="field-input" data-field="features.${index}.icon" value="${feature.icon || ''}" placeholder="fa-icon">
                    </div>
                    <div class="mb-2">
                        <input type="text" class="field-input" data-field="features.${index}.title" value="${feature.title || ''}" placeholder="Feature Title">
                    </div>
                    <div>
                        <textarea class="field-input" data-field="features.${index}.description" rows="2">${feature.description || ''}</textarea>
                    </div>
                </div>
            `;
        });

        return `
            <div class="field-group">
                <label class="field-label">Grid Heading</label>
                <input type="text" class="field-input" data-field="heading" value="${data.heading || ''}">
            </div>
            <div class="field-group">
                <label class="field-label">Features</label>
                <div class="features-container">
                    ${featuresHtml}
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="addFeature(this)">Add Feature</button>
            </div>
            ${this.generateBlockPreview(data)}
        `;
    }

    generateImageFields(data) {
        return `
            <div class="field-group">
                <label class="field-label">Image URL</label>
                <input type="text" class="field-input field-url-input" data-field="url" value="${data.url || ''}" placeholder="media://image.jpg">
            </div>
            <div class="field-group">
                <label class="field-label">Alt Text</label>
                <input type="text" class="field-input" data-field="alt" value="${data.alt || ''}">
            </div>
            <div class="field-group">
                <label class="field-label">Caption</label>
                <input type="text" class="field-input" data-field="caption" value="${data.caption || ''}">
            </div>
            ${this.generateBlockPreview(data)}
        `;
    }

    generateFooterFields(data) {
        const links = data.links || [];
        let linksHtml = '';
        
        links.forEach((link, index) => {
            linksHtml += `
                <div class="link-item" data-index="${index}">
                    <div class="d-flex justify-content-between mb-2">
                        <strong>Link ${index + 1}</strong>
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeLink(this)">Remove</button>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" class="field-input" data-field="links.${index}.label" value="${link.label || ''}" placeholder="Link Label">
                        </div>
                        <div class="col-md-6">
                            <input type="text" class="field-input field-url-input" data-field="links.${index}.url" value="${link.url || ''}" placeholder="/url">
                        </div>
                    </div>
                </div>
            `;
        });

        return `
            <div class="field-group">
                <label class="field-label">Links</label>
                <div class="links-container">
                    ${linksHtml}
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="addLink(this)">Add Link</button>
            </div>
            <div class="field-group">
                <label class="field-label">Copyright</label>
                <input type="text" class="field-input" data-field="copyright" value="${data.copyright || ''}">
            </div>
            ${this.generateBlockPreview(data)}
        `;
    }

    generateCustomFields(data) {
        return `
            <div class="field-group">
                <label class="field-label">Custom JSON Data</label>
                <textarea class="field-input field-textarea" data-field="_json" rows="10">${JSON.stringify(data, null, 2)}</textarea>
                <small class="text-muted">Edit the JSON structure directly. Must be valid JSON.</small>
            </div>
        `;
    }

    generateBlockPreview(data) {
        return `
            <div class="block-preview">
                <h6>Preview Data</h6>
                <pre><code>${JSON.stringify(data, null, 2)}</code></pre>
            </div>
        `;
    }

    updateBlockData(input) {
        const blockElement = input.closest('.block-editor');
        const blockId = blockElement.getAttribute('data-block-id');
        const field = input.getAttribute('data-field');
        const value = input.value;

        const block = this.blocks.find(b => b.id === blockId);
        if (!block) return;

        if (field === '_json') {
            try {
                block.data = JSON.parse(value);
            } catch (e) {
                this.showValidationError(input, 'Invalid JSON format');
                return;
            }
        } else {
            this.setNestedValue(block.data, field, value);
        }

        // Update preview
        const preview = blockElement.querySelector('.block-preview pre code');
        if (preview) {
            preview.textContent = JSON.stringify(block.data, null, 2);
        }

        this.clearValidationError(input);
    }

    setNestedValue(obj, path, value) {
        const keys = path.split('.');
        let current = obj;
        
        for (let i = 0; i < keys.length - 1; i++) {
            const key = keys[i];
            if (!(key in current) || typeof current[key] !== 'object') {
                current[key] = {};
            }
            current = current[key];
        }
        
        current[keys[keys.length - 1]] = value;
    }

    generateBlockId(type) {
        const timestamp = Date.now().toString(36);
        const random = Math.random().toString(36).substr(2, 5);
        return `${type}_${timestamp}_${random}`;
    }

    getDefaultBlockData(type) {
        const defaults = {
            hero: {
                heading: 'Welcome',
                subheading: 'Enter your subheading here',
                background_image: '',
                cta: {
                    text: 'Learn More',
                    url: '#'
                }
            },
            text: {
                heading: 'Section Title',
                content: 'Enter your content here...'
            },
            feature_grid: {
                heading: 'Features',
                features: []
            },
            image: {
                url: '',
                alt: '',
                caption: ''
            },
            footer: {
                links: [],
                copyright: '© 2025 Your Company'
            },
            custom: {}
        };

        return defaults[type] || {};
    }

    async savePage() {
        const pageData = this.collectPageData();
        
        if (!this.validatePageData(pageData)) {
            return;
        }

        this.showSaveStatus('Saving...', 'info');
        
        try {
            const url = this.pageId 
                ? `${this.apiBaseUrl}/pages/${this.pageId}`
                : `${this.apiBaseUrl}/pages`;
            
            const method = this.pageId ? 'PUT' : 'POST';
            
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(pageData)
            });

            const result = await response.json();

            if (response.ok) {
                this.pageId = result.id;
                this.currentVersion = result.version;
                this.isDirty = false;
                this.showSaveStatus('Saved successfully!', 'success');
                
                // Update version display
                document.querySelector('.text-muted').textContent = `Version: ${result.version}`;
            } else {
                throw new Error(result.error || 'Save failed');
            }
        } catch (error) {
            console.error('Save error:', error);
            this.showSaveStatus('Save failed: ' + error.message, 'danger');
        }
    }

    collectPageData() {
        return {
            name: document.getElementById('pageName').value,
            display_name: document.getElementById('displayName').value,
            type: document.getElementById('pageType').value,
            locale: document.getElementById('pageLocale').value,
            version: this.currentVersion,
            value: {
                version: 1,
                title: document.getElementById('displayName').value,
                locale: document.getElementById('pageLocale').value,
                blocks: this.blocks
            }
        };
    }

    validatePageData(data) {
        const errors = [];
        
        if (!data.name) errors.push('Page name is required');
        if (!data.display_name) errors.push('Display name is required');
        if (!data.value.blocks.length) errors.push('At least one block is required');
        
        // Validate block IDs are unique
        const blockIds = data.value.blocks.map(b => b.id);
        const uniqueIds = [...new Set(blockIds)];
        if (blockIds.length !== uniqueIds.length) {
            errors.push('Block IDs must be unique');
        }

        if (errors.length > 0) {
            alert('Validation errors:\n' + errors.join('\n'));
            return false;
        }

        return true;
    }

    showSaveStatus(message, type) {
        const statusEl = document.createElement('div');
        statusEl.className = `alert alert-${type} save-status`;
        statusEl.textContent = message;
        
        document.body.appendChild(statusEl);
        
        setTimeout(() => {
            statusEl.remove();
        }, 3000);
    }

    showValidationError(input, message) {
        this.clearValidationError(input);
        const errorEl = document.createElement('div');
        errorEl.className = 'validation-error';
        errorEl.textContent = message;
        input.parentNode.appendChild(errorEl);
    }

    clearValidationError(input) {
        const existing = input.parentNode.querySelector('.validation-error');
        if (existing) existing.remove();
    }

    updatePageTitle() {
        const displayName = document.getElementById('displayName').value;
        document.getElementById('pageTitle').textContent = displayName || 'New Page';
    }
}

// Global functions for template use
function addFeature(button) {
    const container = button.previousElementSibling;
    const index = container.children.length;
    
    const featureHtml = `
        <div class="feature-item" data-index="${index}">
            <div class="d-flex justify-content-between">
                <strong>Feature ${index + 1}</strong>
                <button type="button" class="btn btn-danger btn-sm btn-remove-feature" onclick="removeFeature(this)">Remove</button>
            </div>
            <div class="mb-2">
                <input type="text" class="field-input" data-field="features.${index}.icon" placeholder="fa-icon">
            </div>
            <div class="mb-2">
                <input type="text" class="field-input" data-field="features.${index}.title" placeholder="Feature Title">
            </div>
            <div>
                <textarea class="field-input" data-field="features.${index}.description" rows="2"></textarea>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', featureHtml);
}

function removeFeature(button) {
    const featureItem = button.closest('.feature-item');
    featureItem.remove();
}

function addLink(button) {
    const container = button.previousElementSibling;
    const index = container.children.length;
    
    const linkHtml = `
        <div class="link-item" data-index="${index}">
            <div class="d-flex justify-content-between mb-2">
                <strong>Link ${index + 1}</strong>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeLink(this)">Remove</button>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <input type="text" class="field-input" data-field="links.${index}.label" placeholder="Link Label">
                </div>
                <div class="col-md-6">
                    <input type="text" class="field-input field-url-input" data-field="links.${index}.url" placeholder="/url">
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', linkHtml);
}

function removeLink(button) {
    const linkItem = button.closest('.link-item');
    linkItem.remove();
}

function moveBlockUp(button) {
    const blockElement = button.closest('.block-editor');
    const previousElement = blockElement.previousElementSibling;
    
    if (previousElement) {
        blockElement.parentNode.insertBefore(blockElement, previousElement);
        window.contentEditor.isDirty = true;
    }
}

function moveBlockDown(button) {
    const blockElement = button.closest('.block-editor');
    const nextElement = blockElement.nextElementSibling;
    
    if (nextElement) {
        blockElement.parentNode.insertBefore(nextElement, blockElement);
        window.contentEditor.isDirty = true;
    }
}

function removeBlock(button) {
    const blockElement = button.closest('.block-editor');
    const blockId = blockElement.getAttribute('data-block-id');
    
    if (confirm('Are you sure you want to remove this block?')) {
        blockElement.classList.add('removing');
        
        setTimeout(() => {
            blockElement.remove();
            
            // Remove from blocks array
            const index = window.contentEditor.blocks.findIndex(b => b.id === blockId);
            if (index !== -1) {
                window.contentEditor.blocks.splice(index, 1);
            }
            
            window.contentEditor.isDirty = true;
            
            // Show add block prompt if no blocks left
            if (window.contentEditor.blocks.length === 0) {
                document.getElementById('addBlockPrompt').style.display = 'block';
            }
        }, 300);
    }
}

function togglePreview() {
    // TODO: Implement preview functionality
    alert('Preview functionality will be implemented based on your frontend structure');
}

function previewChanges() {
    // TODO: Implement preview functionality
    alert('Preview functionality will be implemented based on your frontend structure');
}

function addBlock(type) {
    window.contentEditor.addBlock(type);
}

function savePage() {
    window.contentEditor.savePage();
}

function initializeEditor(pageData, pageId, version) {
    window.contentEditor = new ContentEditor();
    window.contentEditor.initializeEditor(pageData, pageId, version);
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    if (!window.contentEditor) {
        window.contentEditor = new ContentEditor();
    }
});