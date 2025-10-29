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
                
                // Handle field definition changes
                if (e.target.classList.contains('field-definition')) {
                    this.updateFieldDefinition(e.target);
                } else {
                    this.updateBlockData(e.target);
                }
            }
        });

        // Handle select changes for field definitions
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('field-definition')) {
                this.isDirty = true;
                this.updateFieldDefinition(e.target);
                
                // If field type changed, regenerate the field definition UI
                if (e.target.getAttribute('data-def-field') === 'type') {
                    this.regenerateFieldDefinition(e.target);
                }
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
            case 'custom_block':
                return this.generateCustomFields(block.data);
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
        // Check if this is a structured custom block with _fields definition
        if (data._fields && Array.isArray(data._fields)) {
            return this.generateStructuredCustomFields(data);
        }
        
        return `
            <div class="field-group">
                <label class="field-label">Custom JSON Data</label>
                <textarea class="field-input field-textarea" data-field="_json" rows="10">${JSON.stringify(data, null, 2)}</textarea>
                <small class="text-muted">Edit the JSON structure directly. Must be valid JSON.</small>
            </div>
            <div class="field-group">
                <button type="button" class="btn btn-outline-info btn-sm" onclick="convertToStructuredBlock(this)">
                    <i class="fas fa-magic"></i> Convert to Structured Block
                </button>
                <small class="text-muted d-block mt-1">Create a user-friendly form interface for this block</small>
            </div>
        `;
    }

    generateStructuredCustomFields(data) {
        const fields = data._fields || [];
        let fieldsHtml = '';
        let dataFieldsHtml = '';

        // Generate field definition editor
        fields.forEach((field, index) => {
            fieldsHtml += `
                <div class="custom-field-definition" data-field-index="${index}">
                    <div class="card mb-2">
                        <div class="card-header py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">Field ${index + 1}</small>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeCustomField(this, ${index})">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body py-2">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="field-label">Field Key</label>
                                    <input type="text" class="field-input field-definition" data-def-field="key" data-def-index="${index}" value="${field.key || ''}" placeholder="field_name">
                                </div>
                                <div class="col-md-4">
                                    <label class="field-label">Field Type</label>
                                    <select class="field-input field-definition" data-def-field="type" data-def-index="${index}">
                                        <option value="text" ${field.type === 'text' ? 'selected' : ''}>Text</option>
                                        <option value="textarea" ${field.type === 'textarea' ? 'selected' : ''}>Textarea</option>
                                        <option value="url" ${field.type === 'url' ? 'selected' : ''}>URL</option>
                                        <option value="email" ${field.type === 'email' ? 'selected' : ''}>Email</option>
                                        <option value="number" ${field.type === 'number' ? 'selected' : ''}>Number</option>
                                        <option value="select" ${field.type === 'select' ? 'selected' : ''}>Select</option>
                                        <option value="checkbox" ${field.type === 'checkbox' ? 'selected' : ''}>Checkbox</option>
                                        <option value="group" ${field.type === 'group' ? 'selected' : ''}>Group</option>
                                        <option value="array" ${field.type === 'array' ? 'selected' : ''}>Array</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="field-label">Label</label>
                                    <input type="text" class="field-input field-definition" data-def-field="label" data-def-index="${index}" value="${field.label || ''}" placeholder="Field Label">
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <label class="field-label">Placeholder</label>
                                    <input type="text" class="field-input field-definition" data-def-field="placeholder" data-def-index="${index}" value="${field.placeholder || ''}" placeholder="Placeholder text">
                                </div>
                                <div class="col-md-6">
                                    <label class="field-label">Default Value</label>
                                    <input type="text" class="field-input field-definition" data-def-field="default" data-def-index="${index}" value="${field.default || ''}" placeholder="Default value">
                                </div>
                            </div>
                            ${field.type === 'select' ? `
                                <div class="mt-2">
                                    <label class="field-label">Options (one per line)</label>
                                    <textarea class="field-input field-definition" data-def-field="options" data-def-index="${index}" rows="3" placeholder="option1&#10;option2&#10;option3">${(field.options || []).join('\n')}</textarea>
                                </div>
                            ` : ''}
                            ${field.type === 'group' || field.type === 'array' ? `
                                <div class="mt-2">
                                    <label class="field-label">Sub-fields (JSON)</label>
                                    <textarea class="field-input field-definition" data-def-field="subfields" data-def-index="${index}" rows="3" placeholder='[{"key": "title", "type": "text", "label": "Title"}]'>${JSON.stringify(field.subfields || [], null, 2)}</textarea>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
        });

        // Generate actual data input fields based on field definitions
        fields.forEach((field) => {
            if (field.key) {
                dataFieldsHtml += this.generateCustomDataField(field, data[field.key]);
            }
        });

        return `
            <div class="custom-block-builder">
                <div class="field-group">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="field-label mb-0">Field Definitions</label>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addCustomField(this)">
                            <i class="fas fa-plus"></i> Add Field
                        </button>
                    </div>
                    <div class="custom-fields-definitions">
                        ${fieldsHtml}
                    </div>
                </div>
                
                <hr>
                
                <div class="field-group">
                    <label class="field-label">Content Data</label>
                    <div class="custom-data-fields">
                        ${dataFieldsHtml}
                    </div>
                </div>
                
                <div class="field-group">
                    <button type="button" class="btn btn-outline-warning btn-sm" onclick="switchToJsonEditor(this)">
                        <i class="fas fa-code"></i> Switch to JSON Editor
                    </button>
                </div>
            </div>
            ${this.generateBlockPreview(data)}
        `;
    }

    generateCustomDataField(fieldDef, value = null) {
        const fieldValue = value !== null ? value : (fieldDef.default || '');
        
        switch (fieldDef.type) {
            case 'text':
            case 'email':
            case 'url':
                return `
                    <div class="field-group">
                        <label class="field-label">${fieldDef.label || fieldDef.key}</label>
                        <input type="${fieldDef.type}" class="field-input" data-field="${fieldDef.key}" value="${fieldValue}" placeholder="${fieldDef.placeholder || ''}">
                    </div>
                `;
            
            case 'number':
                return `
                    <div class="field-group">
                        <label class="field-label">${fieldDef.label || fieldDef.key}</label>
                        <input type="number" class="field-input" data-field="${fieldDef.key}" value="${fieldValue}" placeholder="${fieldDef.placeholder || ''}">
                    </div>
                `;
            
            case 'textarea':
                return `
                    <div class="field-group">
                        <label class="field-label">${fieldDef.label || fieldDef.key}</label>
                        <textarea class="field-input field-textarea" data-field="${fieldDef.key}" rows="4" placeholder="${fieldDef.placeholder || ''}">${fieldValue}</textarea>
                    </div>
                `;
            
            case 'select':
                const options = fieldDef.options || [];
                let optionsHtml = options.map(option => 
                    `<option value="${option}" ${fieldValue === option ? 'selected' : ''}>${option}</option>`
                ).join('');
                
                return `
                    <div class="field-group">
                        <label class="field-label">${fieldDef.label || fieldDef.key}</label>
                        <select class="field-input" data-field="${fieldDef.key}">
                            <option value="">Select ${fieldDef.label || fieldDef.key}</option>
                            ${optionsHtml}
                        </select>
                    </div>
                `;
            
            case 'checkbox':
                return `
                    <div class="field-group">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" data-field="${fieldDef.key}" ${fieldValue ? 'checked' : ''}>
                            <label class="form-check-label">${fieldDef.label || fieldDef.key}</label>
                        </div>
                    </div>
                `;
            
            case 'group':
                return `
                    <div class="field-group">
                        <label class="field-label">${fieldDef.label || fieldDef.key}</label>
                        <div class="custom-group-field border rounded p-3" data-field="${fieldDef.key}">
                            <small class="text-muted">Group field - implement sub-fields rendering</small>
                        </div>
                    </div>
                `;
                
            case 'array':
                return `
                    <div class="field-group">
                        <label class="field-label">${fieldDef.label || fieldDef.key}</label>
                        <div class="custom-array-field" data-field="${fieldDef.key}">
                            <div class="array-items">
                                <!-- Array items will be rendered here -->
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addArrayItem(this, '${fieldDef.key}')">
                                <i class="fas fa-plus"></i> Add Item
                            </button>
                        </div>
                    </div>
                `;
            
            default:
                return `
                    <div class="field-group">
                        <label class="field-label">${fieldDef.label || fieldDef.key}</label>
                        <input type="text" class="field-input" data-field="${fieldDef.key}" value="${fieldValue}" placeholder="${fieldDef.placeholder || ''}">
                    </div>
                `;
        }
    }

    generateBlockPreview(data) {
        return `
            <div class="block-preview">
                <h6>Preview Data</h6>
                <pre><code>${JSON.stringify(data, null, 2)}</code></pre>
            </div>
        `;
    }

    updateFieldDefinition(input) {
        const blockElement = input.closest('.block-editor');
        const blockId = blockElement.getAttribute('data-block-id');
        const defField = input.getAttribute('data-def-field');
        const defIndex = parseInt(input.getAttribute('data-def-index'));
        
        const block = this.blocks.find(b => b.id === blockId);
        if (!block) return;

        // Initialize _fields array if it doesn't exist
        if (!block.data._fields) {
            block.data._fields = [];
        }

        // Ensure field definition exists at this index
        while (block.data._fields.length <= defIndex) {
            block.data._fields.push({});
        }

        let value = input.value;
        
        // Handle special field types
        if (defField === 'options') {
            value = value.split('\n').filter(opt => opt.trim());
        } else if (defField === 'subfields') {
            try {
                value = JSON.parse(value);
            } catch (e) {
                this.showValidationError(input, 'Invalid JSON for subfields');
                return;
            }
        }

        // Update field definition
        block.data._fields[defIndex][defField] = value;

        // If key changed, regenerate the data fields
        if (defField === 'key' || defField === 'type' || defField === 'options' || defField === 'subfields') {
            this.regenerateCustomDataFields(blockElement, block);
        }

        // Update preview
        this.updateBlockPreview(blockElement, block);
        this.clearValidationError(input);
    }

    regenerateCustomDataFields(blockElement, block) {
        const dataFieldsContainer = blockElement.querySelector('.custom-data-fields');
        if (!dataFieldsContainer) return;

        let dataFieldsHtml = '';
        const fields = block.data._fields || [];
        
        fields.forEach((field) => {
            if (field.key) {
                dataFieldsHtml += this.generateCustomDataField(field, block.data[field.key]);
            }
        });

        dataFieldsContainer.innerHTML = dataFieldsHtml;
    }

    regenerateFieldDefinition(selectElement) {
        const defIndex = parseInt(selectElement.getAttribute('data-def-index'));
        const newType = selectElement.value;
        const fieldDef = selectElement.closest('.custom-field-definition');
        const cardBody = fieldDef.querySelector('.card-body');
        
        // Get current values
        const currentKey = fieldDef.querySelector('[data-def-field="key"]').value;
        const currentLabel = fieldDef.querySelector('[data-def-field="label"]').value;
        const currentPlaceholder = fieldDef.querySelector('[data-def-field="placeholder"]').value;
        const currentDefault = fieldDef.querySelector('[data-def-field="default"]').value;
        
        // Regenerate the card body content
        cardBody.innerHTML = `
            <div class="row">
                <div class="col-md-4">
                    <label class="field-label">Field Key</label>
                    <input type="text" class="field-input field-definition" data-def-field="key" data-def-index="${defIndex}" value="${currentKey}" placeholder="field_name">
                </div>
                <div class="col-md-4">
                    <label class="field-label">Field Type</label>
                    <select class="field-input field-definition" data-def-field="type" data-def-index="${defIndex}">
                        <option value="text" ${newType === 'text' ? 'selected' : ''}>Text</option>
                        <option value="textarea" ${newType === 'textarea' ? 'selected' : ''}>Textarea</option>
                        <option value="url" ${newType === 'url' ? 'selected' : ''}>URL</option>
                        <option value="email" ${newType === 'email' ? 'selected' : ''}>Email</option>
                        <option value="number" ${newType === 'number' ? 'selected' : ''}>Number</option>
                        <option value="select" ${newType === 'select' ? 'selected' : ''}>Select</option>
                        <option value="checkbox" ${newType === 'checkbox' ? 'selected' : ''}>Checkbox</option>
                        <option value="group" ${newType === 'group' ? 'selected' : ''}>Group</option>
                        <option value="array" ${newType === 'array' ? 'selected' : ''}>Array</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="field-label">Label</label>
                    <input type="text" class="field-input field-definition" data-def-field="label" data-def-index="${defIndex}" value="${currentLabel}" placeholder="Field Label">
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-6">
                    <label class="field-label">Placeholder</label>
                    <input type="text" class="field-input field-definition" data-def-field="placeholder" data-def-index="${defIndex}" value="${currentPlaceholder}" placeholder="Placeholder text">
                </div>
                <div class="col-md-6">
                    <label class="field-label">Default Value</label>
                    <input type="text" class="field-input field-definition" data-def-field="default" data-def-index="${defIndex}" value="${currentDefault}" placeholder="Default value">
                </div>
            </div>
            ${newType === 'select' ? `
                <div class="mt-2">
                    <label class="field-label">Options (one per line)</label>
                    <textarea class="field-input field-definition" data-def-field="options" data-def-index="${defIndex}" rows="3" placeholder="option1&#10;option2&#10;option3"></textarea>
                </div>
            ` : ''}
            ${newType === 'group' || newType === 'array' ? `
                <div class="mt-2">
                    <label class="field-label">Sub-fields (JSON)</label>
                    <textarea class="field-input field-definition" data-def-field="subfields" data-def-index="${defIndex}" rows="3" placeholder='[{"key": "title", "type": "text", "label": "Title"}]'></textarea>
                </div>
            ` : ''}
        `;
    }

    updateBlockPreview(blockElement, block) {
        const preview = blockElement.querySelector('.block-preview pre code');
        if (preview) {
            // Create clean data without _fields for preview
            const cleanData = { ...block.data };
            delete cleanData._fields;
            preview.textContent = JSON.stringify(cleanData, null, 2);
        }
    }

    updateBlockData(input) {
        const blockElement = input.closest('.block-editor');
        const blockId = blockElement.getAttribute('data-block-id');
        const field = input.getAttribute('data-field');
        let value = input.value;

        const block = this.blocks.find(b => b.id === blockId);
        if (!block) return;

        // Handle checkbox inputs
        if (input.type === 'checkbox') {
            value = input.checked;
        }

        if (field === '_json') {
            try {
                const parsed = JSON.parse(value);
                // Preserve _fields if they exist
                if (block.data._fields) {
                    parsed._fields = block.data._fields;
                }
                block.data = parsed;
            } catch (e) {
                this.showValidationError(input, 'Invalid JSON format');
                return;
            }
        } else {
            this.setNestedValue(block.data, field, value);
        }

        // Update preview
        this.updateBlockPreview(blockElement, block);
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
            custom_block: {
                _fields: [
                    {
                        key: 'title',
                        type: 'text',
                        label: 'Title',
                        placeholder: 'Enter title',
                        default: ''
                    },
                    {
                        key: 'content',
                        type: 'textarea',
                        label: 'Content',
                        placeholder: 'Enter content',
                        default: ''
                    }
                ],
                title: 'Sample Title',
                content: 'Sample content'
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

// Custom field management functions
function addCustomField(button) {
    const container = button.closest('.field-group').querySelector('.custom-fields-definitions');
    const index = container.children.length;
    
    const fieldHtml = `
        <div class="custom-field-definition" data-field-index="${index}">
            <div class="card mb-2">
                <div class="card-header py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">Field ${index + 1}</small>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeCustomField(this, ${index})">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body py-2">
                    <div class="row">
                        <div class="col-md-4">
                            <label class="field-label">Field Key</label>
                            <input type="text" class="field-input field-definition" data-def-field="key" data-def-index="${index}" placeholder="field_name">
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Field Type</label>
                            <select class="field-input field-definition" data-def-field="type" data-def-index="${index}">
                                <option value="text">Text</option>
                                <option value="textarea">Textarea</option>
                                <option value="url">URL</option>
                                <option value="email">Email</option>
                                <option value="number">Number</option>
                                <option value="select">Select</option>
                                <option value="checkbox">Checkbox</option>
                                <option value="group">Group</option>
                                <option value="array">Array</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Label</label>
                            <input type="text" class="field-input field-definition" data-def-field="label" data-def-index="${index}" placeholder="Field Label">
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <label class="field-label">Placeholder</label>
                            <input type="text" class="field-input field-definition" data-def-field="placeholder" data-def-index="${index}" placeholder="Placeholder text">
                        </div>
                        <div class="col-md-6">
                            <label class="field-label">Default Value</label>
                            <input type="text" class="field-input field-definition" data-def-field="default" data-def-index="${index}" placeholder="Default value">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', fieldHtml);
    window.contentEditor.isDirty = true;
}

function removeCustomField(button, index) {
    const fieldDef = button.closest('.custom-field-definition');
    
    if (confirm('Remove this field definition? This will also remove its data.')) {
        fieldDef.remove();
        
        // Update field indices and regenerate data fields
        const blockElement = button.closest('.block-editor');
        const blockId = blockElement.getAttribute('data-block-id');
        const block = window.contentEditor.blocks.find(b => b.id === blockId);
        
        if (block && block.data._fields) {
            block.data._fields.splice(index, 1);
            window.contentEditor.regenerateCustomDataFields(blockElement, block);
            window.contentEditor.updateBlockPreview(blockElement, block);
        }
        
        window.contentEditor.isDirty = true;
    }
}

function convertToStructuredBlock(button) {
    const blockElement = button.closest('.block-editor');
    const blockId = blockElement.getAttribute('data-block-id');
    const block = window.contentEditor.blocks.find(b => b.id === blockId);
    
    if (!block) return;
    
    // Initialize with basic fields
    block.data._fields = [
        {
            key: 'title',
            type: 'text',
            label: 'Title',
            placeholder: 'Enter title',
            default: ''
        }
    ];
    
    // Add title field if it doesn't exist
    if (!block.data.title) {
        block.data.title = '';
    }
    
    // Regenerate the block content
    const contentDiv = blockElement.querySelector('.block-content');
    contentDiv.innerHTML = window.contentEditor.generateCustomFields(block.data);
    
    window.contentEditor.isDirty = true;
}

function switchToJsonEditor(button) {
    const blockElement = button.closest('.block-editor');
    const blockId = blockElement.getAttribute('data-block-id');
    const block = window.contentEditor.blocks.find(b => b.id === blockId);
    
    if (!block) return;
    
    if (confirm('Switch to JSON editor? You will lose the structured field definitions.')) {
        // Remove _fields from data
        const cleanData = { ...block.data };
        delete cleanData._fields;
        block.data = cleanData;
        
        // Regenerate as simple JSON editor
        const contentDiv = blockElement.querySelector('.block-content');
        contentDiv.innerHTML = `
            <div class="field-group">
                <label class="field-label">Custom JSON Data</label>
                <textarea class="field-input field-textarea" data-field="_json" rows="10">${JSON.stringify(cleanData, null, 2)}</textarea>
                <small class="text-muted">Edit the JSON structure directly. Must be valid JSON.</small>
            </div>
            <div class="field-group">
                <button type="button" class="btn btn-outline-info btn-sm" onclick="convertToStructuredBlock(this)">
                    <i class="fas fa-magic"></i> Convert to Structured Block
                </button>
                <small class="text-muted d-block mt-1">Create a user-friendly form interface for this block</small>
            </div>
        `;
        
        window.contentEditor.isDirty = true;
    }
}

function addArrayItem(button, fieldKey) {
    // TODO: Implement array item management
    alert('Array field management will be implemented in the next iteration');
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