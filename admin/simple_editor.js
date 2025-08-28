// Simple Rich Text Editor als fallback voor TinyMCE
class SimpleEditor {
    constructor(elementId) {
        this.elementId = elementId;
        this.element = document.getElementById(elementId);
        this.init();
    }

    init() {
        // Wrap the textarea in a container
        const wrapper = document.createElement('div');
        wrapper.className = 'simple-editor-wrapper';
        wrapper.style.border = '1px solid #ddd';
        wrapper.style.borderRadius = '4px';
        wrapper.style.overflow = 'hidden';

        // Create toolbar
        const toolbar = document.createElement('div');
        toolbar.className = 'simple-editor-toolbar';
        toolbar.style.background = '#f8f9fa';
        toolbar.style.padding = '10px';
        toolbar.style.borderBottom = '1px solid #ddd';
        toolbar.innerHTML = `
            <button type="button" onclick="SimpleEditor.execCommand('bold')" title="Vet (Ctrl+B)">
                <b>B</b>
            </button>
            <button type="button" onclick="SimpleEditor.execCommand('italic')" title="Cursief (Ctrl+I)">
                <i>I</i>
            </button>
            <button type="button" onclick="SimpleEditor.execCommand('underline')" title="Onderstreept (Ctrl+U)">
                <u>U</u>
            </button>
            <button type="button" onclick="SimpleEditor.execCommand('insertUnorderedList')" title="Opsommingslijst">
                • List
            </button>
            <button type="button" onclick="SimpleEditor.execCommand('insertOrderedList')" title="Genummerde lijst">
                1. List
            </button>
            <button type="button" onclick="SimpleEditor.addImage('${elementId}')" title="Foto uploaden via bestand kiezen" style="background: #e3f2fd;">
                📷 Bestand
            </button>
            <button type="button" onclick="SimpleEditor.testClipboard('${elementId}')" title="Test of Ctrl+V werkt in je browser" style="background: #fff3cd; color: #856404; border: 1px solid #ffeaa7;">
                🧪 Test Ctrl+V
            </button>
            <div style="display: inline-block; margin-left: 10px; font-size: 12px; color: #666; font-style: italic;">
                💡 Tip: Ctrl+V om foto's te plakken | Sleep foto's in het tekstveld
            </div>
            <input type="file" id="${elementId}_file" accept="image/*" style="display:none" onchange="SimpleEditor.uploadImage('${elementId}', this)">
        `;

        // Style toolbar buttons
        const buttons = toolbar.querySelectorAll('button');
        buttons.forEach(btn => {
            btn.style.margin = '0 5px';
            btn.style.padding = '5px 10px';
            btn.style.border = '1px solid #ccc';
            btn.style.background = 'white';
            btn.style.borderRadius = '3px';
            btn.style.cursor = 'pointer';
        });

        // Create content editable div
        const editor = document.createElement('div');
        editor.contentEditable = true;
        editor.style.cssText = `
            min-height: 200px;
            padding: 15px;
            background: white;
            outline: none;
            font-family: Inter, Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            border: none;
            transition: background-color 0.3s ease;
        `;
        editor.innerHTML = this.element.value || '';
        
        // Placeholder tekst
        if (!this.element.value) {
            editor.innerHTML = '<p style="color: #999; margin: 0;">Begin met typen... Of plak een afbeelding met Ctrl+V</p>';
        }

        // Store reference voor later gebruik
        this.editor = editor;

        // Sync content back to textarea
        editor.addEventListener('input', () => {
            this.element.value = editor.innerHTML;
        });

        // Keyboard shortcuts
        editor.addEventListener('keydown', (e) => {
            // Ctrl+V wordt al afgehandeld door paste event
            // Hier kunnen we extra shortcuts toevoegen indien nodig
            
            if (e.ctrlKey || e.metaKey) {
                switch(e.key.toLowerCase()) {
                    case 'b':
                        e.preventDefault();
                        document.execCommand('bold', false, null);
                        break;
                    case 'i':
                        e.preventDefault();
                        document.execCommand('italic', false, null);
                        break;
                    case 'u':
                        e.preventDefault();
                        document.execCommand('underline', false, null);
                        break;
                }
            }
        });

        // Focus/blur events voor placeholder
        editor.addEventListener('focus', () => {
            if (editor.innerHTML.includes('Begin met typen')) {
                editor.innerHTML = '';
            }
        });

        editor.addEventListener('blur', () => {
            if (editor.innerHTML.trim() === '' || editor.innerHTML === '<p><br></p>' || editor.innerHTML === '<br>') {
                editor.innerHTML = '<p style="color: #999; margin: 0;">Begin met typen... Of plak een afbeelding met Ctrl+V</p>';
            }
        });

        // Clipboard paste event voor afbeeldingen (verbeterde versie)
        editor.addEventListener('paste', (e) => {
            console.log('Paste event gedetecteerd');
            
            // Check clipboardData
            if (!e.clipboardData || !e.clipboardData.items) {
                console.log('Geen clipboardData gevonden');
                return;
            }
            
            const items = e.clipboardData.items;
            console.log('Clipboard items:', items.length);
            
            let imageFound = false;
            
            for (let i = 0; i < items.length; i++) {
                const item = items[i];
                console.log('Item type:', item.type, 'Kind:', item.kind);
                
                // Check if it's an image
                if (item.type && item.type.startsWith('image/')) {
                    console.log('Afbeelding gevonden in klembord!');
                    e.preventDefault(); // Voorkom standaard paste
                    
                    const blob = item.getAsFile();
                    if (blob) {
                        imageFound = true;
                        this.uploadClipboardImage(blob);
                        this.showMessage('📋 Afbeelding gedetecteerd, uploaden...', 'success');
                    }
                    break;
                }
            }
            
            if (!imageFound) {
                console.log('Geen afbeelding gevonden in klembord');
                // Toon hint alleen als er geen afbeelding was
                setTimeout(() => {
                    this.showMessage('💡 Tip: Kopieer eerst een afbeelding (rechtsklik → kopiëren) en probeer dan Ctrl+V', 'info');
                }, 100);
            }
        });

        // Drag & Drop ondersteuning
        editor.addEventListener('dragover', (e) => {
            e.preventDefault();
            editor.style.background = '#e8f5e8';
            editor.style.border = '2px dashed #28a745';
            
            // Voeg overlay toe als die er nog niet is
            if (!editor.querySelector('.drag-overlay')) {
                const overlay = document.createElement('div');
                overlay.className = 'drag-overlay';
                overlay.style.cssText = `
                    position: absolute; top: 0; left: 0; right: 0; bottom: 0;
                    background: rgba(40, 167, 69, 0.1);
                    display: flex; align-items: center; justify-content: center;
                    font-size: 18px; font-weight: bold; color: #28a745;
                    pointer-events: none; z-index: 1000;
                `;
                overlay.innerHTML = '📷 Sleep je afbeelding hier!';
                wrapper.style.position = 'relative';
                wrapper.appendChild(overlay);
            }
        });

        editor.addEventListener('dragleave', (e) => {
            e.preventDefault();
            editor.style.background = 'white';
            editor.style.border = 'none';
            
            // Verwijder overlay
            const overlay = wrapper.querySelector('.drag-overlay');
            if (overlay) {
                overlay.remove();
            }
        });

        editor.addEventListener('drop', (e) => {
            e.preventDefault();
            editor.style.background = 'white';
            editor.style.border = 'none';
            
            // Verwijder overlay
            const overlay = wrapper.querySelector('.drag-overlay');
            if (overlay) {
                overlay.remove();
            }
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const file = files[0];
                if (file.type.indexOf('image') !== -1) {
                    this.uploadClipboardImage(file);
                } else {
                    this.showMessage('❌ Alleen afbeeldingsbestanden zijn toegestaan', 'error');
                }
            }
        });

        // Insert before textarea and hide textarea
        this.element.parentNode.insertBefore(wrapper, this.element);
        wrapper.appendChild(toolbar);
        wrapper.appendChild(editor);
        this.element.style.display = 'none';
    }

    static execCommand(command) {
        document.execCommand(command, false, null);
    }

    static addImage(elementId) {
        document.getElementById(elementId + '_file').click();
    }

    static uploadImage(elementId, fileInput) {
        const file = fileInput.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('file', file);

        fetch('upload_image.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const img = `<br><img src="${result.url}" style="max-width: 100%; height: auto; margin: 10px 0; border-radius: 4px;"><br>`;
                document.execCommand('insertHTML', false, img);
            } else {
                alert('Fout bij uploaden: ' + (result.error || 'Onbekende fout'));
            }
        })
        .catch(error => {
            alert('Netwerk fout: ' + error.message);
        });
    }

    static async testClipboard(elementId) {
        const editor = window.descriptionEditor || window.editDescriptionEditor;
        
        if (!editor) {
            alert('Editor niet gevonden!');
            return;
        }

        try {
            // Check if clipboard API is available
            if (!navigator.clipboard) {
                editor.showMessage('❌ Clipboard API niet ondersteund in deze browser. Probeer Chrome, Firefox of Edge.', 'error');
                return;
            }

            // Test clipboard read permission
            try {
                const clipboardItems = await navigator.clipboard.read();
                console.log('Clipboard items:', clipboardItems);
                
                let hasImage = false;
                for (const item of clipboardItems) {
                    console.log('Clipboard item types:', item.types);
                    for (const type of item.types) {
                        if (type.startsWith('image/')) {
                            hasImage = true;
                            const blob = await item.getType(type);
                            console.log('Afbeelding gevonden:', blob);
                            editor.uploadClipboardImage(blob);
                            editor.showMessage('✅ Clipboard test geslaagd! Afbeelding wordt geüpload.', 'success');
                            return;
                        }
                    }
                }
                
                if (!hasImage) {
                    editor.showMessage('📋 Geen afbeelding gevonden in klembord. Kopieer eerst een afbeelding (rechtsklik → kopiëren) en probeer opnieuw.', 'info');
                }
                
            } catch (readError) {
                console.log('Clipboard read error:', readError);
                editor.showMessage('⚠️ Geen toegang tot klembord. Probeer Ctrl+V direct in het tekstveld.', 'info');
            }
            
        } catch (error) {
            console.error('Clipboard test error:', error);
            editor.showMessage('❌ Clipboard test mislukt: ' + error.message, 'error');
        }
    }

    getContent() {
        return this.editor.innerHTML;
    }

    setContent(content) {
        this.editor.innerHTML = content;
        this.element.value = content;
    }

    uploadClipboardImage(file) {
        const formData = new FormData();
        formData.append('file', file);

        // Toon loading indicator
        const loadingMsg = document.createElement('div');
        loadingMsg.style.cssText = 'display: inline-block; padding: 5px 10px; background: #e3f2fd; border-radius: 4px; margin: 5px; font-size: 12px;';
        loadingMsg.textContent = '📤 Afbeelding uploaden...';
        
        // Voeg loading bericht toe op cursor positie
        const selection = window.getSelection();
        if (selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            range.insertNode(loadingMsg);
            range.collapse(false);
        } else {
            this.editor.appendChild(loadingMsg);
        }

        fetch('upload_image.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            // Verwijder loading bericht
            loadingMsg.remove();
            
            if (result.success) {
                const img = document.createElement('img');
                img.src = result.url;
                img.style.cssText = 'max-width: 100%; height: auto; margin: 10px 0; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);';
                img.alt = 'Geplakte afbeelding';
                
                // Voeg afbeelding toe op cursor positie
                const selection = window.getSelection();
                if (selection.rangeCount > 0) {
                    const range = selection.getRangeAt(0);
                    range.insertNode(img);
                    range.collapse(false);
                } else {
                    this.editor.appendChild(img);
                }
                
                // Update textarea content
                this.element.value = this.editor.innerHTML;
                
                // Toon succesbericht
                this.showMessage('✅ Afbeelding succesvol geplakt!', 'success');
            } else {
                this.showMessage('❌ Fout bij uploaden: ' + (result.error || 'Onbekende fout'), 'error');
            }
        })
        .catch(error => {
            loadingMsg.remove();
            this.showMessage('❌ Netwerk fout: ' + error.message, 'error');
        });
    }

    showMessage(text, type) {
        const msg = document.createElement('div');
        
        let bgColor, textColor, borderColor;
        if (type === 'success') {
            bgColor = '#d4edda';
            textColor = '#155724';
            borderColor = '#c3e6cb';
        } else if (type === 'error') {
            bgColor = '#f8d7da';
            textColor = '#721c24';
            borderColor = '#f5c6cb';
        } else { // info
            bgColor = '#e3f2fd';
            textColor = '#0d47a1';
            borderColor = '#bbdefb';
        }
        
        msg.style.cssText = `
            position: fixed; top: 20px; right: 20px; z-index: 10000;
            padding: 10px 15px; border-radius: 6px; font-size: 14px;
            background: ${bgColor};
            color: ${textColor};
            border: 1px solid ${borderColor};
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            max-width: 350px;
            word-wrap: break-word;
        `;
        msg.textContent = text;
        document.body.appendChild(msg);
        
        const timeout = type === 'info' ? 5000 : 3000; // Info berichten langer tonen
        setTimeout(() => {
            if (msg.parentNode) {
                msg.parentNode.removeChild(msg);
            }
        }, timeout);
    }
}

// Auto-initialisatie - gebruik Simple Editor direct voor betrouwbaarheid
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        // Gebruik altijd Simple Editor omdat het betrouwbaarder is
        console.log('Initialiseer Simple Editor voor beschrijvingsvelden');
        
        if (document.getElementById('description')) {
            window.descriptionEditor = new SimpleEditor('description');
        }
        
        // Voor edit modal wordt het later geïnitialiseerd wanneer nodig
    }, 1000);
}); 