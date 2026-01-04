// ==========================================
// DASHBOARD ADMIN - SCRIPT
// ==========================================

// Récupérer l'URL admin et le token CSRF depuis l'environnement (injecté dans le layout)
const ADMIN_URL = window.ADMIN_URL || '/admin';
const CSRF_TOKEN = window.CSRF_TOKEN || '';

// Helper: Ajouter automatiquement le token CSRF à un FormData
function addCsrfToken(formData) {
    console.log('CSRF Token disponible:', CSRF_TOKEN);
    if (!formData.has('csrf_token')) {
        formData.append('csrf_token', CSRF_TOKEN);
    }
    // Debug: afficher tous les champs du FormData
    console.log('FormData contents:');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + (pair[1] instanceof File ? pair[1].name : pair[1]));
    }
    return formData;
}

// ==========================================
// GESTION DES MODALES
// ==========================================
class ModalManager {
    constructor(modalId, openButtonId) {
        this.modal = document.getElementById(modalId);
        this.openButton = document.getElementById(openButtonId);
        this.closeButton = this.modal?.querySelector('.close');

        this.init();
    }

    init() {
        if (!this.modal) return;

        // Ouvrir la modale
        if (this.openButton) {
            this.openButton.addEventListener('click', () => this.open());
        }

        // Fermer la modale avec le bouton X
        if (this.closeButton) {
            this.closeButton.addEventListener('click', () => this.close());
        }

        // Fermer avec tous les boutons .close-modal
        const closeModalButtons = this.modal.querySelectorAll('.close-modal');
        closeModalButtons.forEach(btn => {
            btn.addEventListener('click', () => this.close());
        });

        // Fermer en cliquant en dehors
        window.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.close();
            }
        });

        // Fermer avec Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen()) {
                this.close();
            }
        });
    }

    open() {
        if (this.modal) {
            this.modal.style.display = 'flex';
        }
    }

    close() {
        if (this.modal) {
            this.modal.style.display = 'none';
            // Réinitialiser le formulaire si présent
            const form = this.modal.querySelector('form');
            if (form) {
                form.reset();
                const message = this.modal.querySelector('[id$="Message"]');
                if (message) {
                    message.textContent = '';
                }
            }
        }
    }

    isOpen() {
        return this.modal && this.modal.style.display === 'flex';
    }
}

// Initialiser toutes les modales
const modals = {
    skill: new ModalManager('skillModal', 'openSkillModal'),
    project: new ModalManager('projectModal', 'openProjectModal'),
    category: new ModalManager('categoryModal', 'openCategoryModal')
};

// ==========================================
// MODALE SKILL - CRÉATION
// ==========================================
const createSkillForm = document.getElementById('createSkillForm');

if (createSkillForm) {
    createSkillForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = addCsrfToken(new FormData(this));
        const messageDiv = document.getElementById('skillMessage');

        try {
            const response = await fetch(`${ADMIN_URL}/skills/create`, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            messageDiv.textContent = result.message;
            messageDiv.style.color = result.success ? 'green' : 'red';

            if (result.success) {
                this.reset();
                setTimeout(() => {
                    location.reload();
                }, 1000);
            }
        } catch (error) {
            messageDiv.textContent = 'Erreur de connexion';
            messageDiv.style.color = 'red';
        }
    });
}

// ==========================================
// MODALE PROJECT - CRÉATION
// ==========================================
const createProjectForm = document.getElementById('createProjectForm');

if (createProjectForm) {
    createProjectForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = addCsrfToken(new FormData(this));
        const messageDiv = document.getElementById('projectMessage');
        const submitBtn = this.querySelector('button[type="submit"]');

        submitBtn.disabled = true;
        submitBtn.textContent = 'Création...';

        try {
            const response = await fetch(`${ADMIN_URL}/projects/create`, {
                method: 'POST',
                body: formData
            });

            // Vérifier si la réponse est OK
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            // Récupérer le texte brut d'abord
            const responseText = await response.text();
            console.log('Réponse brute:', responseText);

            // Essayer de parser en JSON
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (jsonError) {
                console.error('Erreur JSON:', jsonError);
                console.error('Contenu reçu:', responseText);
                throw new Error('Réponse invalide du serveur. Voir console pour détails.');
            }

            messageDiv.textContent = result.message;
            messageDiv.style.color = result.success ? 'green' : 'red';

            if (result.success) {
                this.reset();
                setTimeout(() => {
                    location.reload();
                }, 1000);
            }
        } catch (error) {
            console.error('Erreur détaillée:', error);
            messageDiv.textContent = 'Erreur: ' + error.message;
            messageDiv.style.color = 'red';
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Ajouter';
        }
    });
}

// ==========================================
// MODALE CATEGORY - CRÉATION
// ==========================================
const createCategoryForm = document.getElementById('createCategoryForm');

if (createCategoryForm) {
    createCategoryForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = addCsrfToken(new FormData(this));
        const messageDiv = document.getElementById('categoryMessage');

        try {
            const response = await fetch(`${ADMIN_URL}/categories/create`, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            messageDiv.textContent = result.message;
            messageDiv.style.color = result.success ? 'green' : 'red';

            if (result.success) {
                this.reset();
                setTimeout(() => {
                    location.reload();
                }, 1000);
            }
        } catch (error) {
            messageDiv.textContent = 'Erreur de connexion';
            messageDiv.style.color = 'red';
        }
    });
}

// ==========================================
// PREVIEW IMAGE UPLOAD
// ==========================================
function setupImagePreview(inputId, previewId) {
    const input = document.querySelector(`input[name="${inputId}"]`);
    if (!input) return;

    input.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Vérifier le type
        if (!file.type.startsWith('image/')) {
            alert('Veuillez sélectionner une image');
            return;
        }

        // Vérifier la taille (5MB max)
        if (file.size > 5 * 1024 * 1024) {
            alert('L\'image ne doit pas dépasser 5MB');
            return;
        }

        // Créer un aperçu
        const reader = new FileReader();
        reader.onload = function(e) {
            // Créer ou mettre à jour l'aperçu
            let preview = document.getElementById(previewId);
            if (!preview) {
                preview = document.createElement('img');
                preview.id = previewId;
                preview.style.cssText = 'max-width: 200px; margin-top: 10px; border-radius: 8px;';
                input.parentElement.appendChild(preview);
            }
            preview.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });
}

// Activer les previews
setupImagePreview('src', 'profilePreview');
setupImagePreview('image', 'projectImagePreview');

// ==========================================
// AMÉLIORATION FORMULAIRE PROJET
// ==========================================

// Skills Filter & Search
const skillsSearch = document.getElementById('skillsSearch');
const skillsSelector = document.getElementById('skillsSelector');
const selectAllSkillsBtn = document.getElementById('selectAllSkills');
const clearAllSkillsBtn = document.getElementById('clearAllSkills');
const selectedSkillsCount = document.getElementById('selectedSkillsCount');
const noSkillsFound = document.getElementById('noSkillsFound');

if (skillsSearch && skillsSelector) {
    // Fonction pour mettre à jour le compteur de skills sélectionnées
    function updateSkillsCounter() {
        const checkedCount = skillsSelector.querySelectorAll('input[type="checkbox"]:checked').length;
        if (selectedSkillsCount) {
            selectedSkillsCount.textContent = checkedCount;
        }
    }

    // Fonction de recherche/filtre
    skillsSearch.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase().trim();
        const skillCheckboxes = skillsSelector.querySelectorAll('.skill-checkbox');
        let visibleCount = 0;

        skillCheckboxes.forEach(label => {
            const skillName = label.getAttribute('data-skill-name') || '';

            if (skillName.includes(searchTerm)) {
                label.classList.remove('hidden');
                visibleCount++;
            } else {
                label.classList.add('hidden');
            }
        });

        // Afficher/cacher le message "aucun résultat"
        if (noSkillsFound) {
            if (visibleCount === 0 && searchTerm !== '') {
                noSkillsFound.style.display = 'block';
                skillsSelector.style.display = 'none';
            } else {
                noSkillsFound.style.display = 'none';
                skillsSelector.style.display = 'grid';
            }
        }
    });

    // Bouton "Tout sélectionner"
    if (selectAllSkillsBtn) {
        selectAllSkillsBtn.addEventListener('click', function() {
            const visibleCheckboxes = skillsSelector.querySelectorAll('.skill-checkbox:not(.hidden) input[type="checkbox"]');
            visibleCheckboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
            updateSkillsCounter();
        });
    }

    // Bouton "Tout effacer"
    if (clearAllSkillsBtn) {
        clearAllSkillsBtn.addEventListener('click', function() {
            const allCheckboxes = skillsSelector.querySelectorAll('input[type="checkbox"]');
            allCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            updateSkillsCounter();
        });
    }

    // Écouter les changements sur les checkboxes pour mettre à jour le compteur
    skillsSelector.addEventListener('change', function(e) {
        if (e.target.type === 'checkbox') {
            updateSkillsCounter();
        }
    });

    // Initialiser le compteur
    updateSkillsCounter();
}

// ==========================================
// CHARACTER COUNTER
// ==========================================

function setupCharacterCounter(textareaId, counterId) {
    const textarea = document.getElementById(textareaId);
    const counter = document.getElementById(counterId);

    if (textarea && counter) {
        // Fonction de mise à jour
        function updateCounter() {
            const length = textarea.value.length;
            counter.textContent = length;

            // Changer la couleur si proche de la limite
            if (textarea.hasAttribute('maxlength')) {
                const maxLength = parseInt(textarea.getAttribute('maxlength'));
                const percentage = (length / maxLength) * 100;

                if (percentage >= 90) {
                    counter.style.color = '#e74c3c'; // Rouge
                } else if (percentage >= 75) {
                    counter.style.color = '#f39c12'; // Orange
                } else {
                    counter.style.color = ''; // Couleur par défaut
                }
            }
        }

        // Écouter les changements
        textarea.addEventListener('input', updateCounter);
        textarea.addEventListener('keyup', updateCounter);

        // Initialiser
        updateCounter();
    }
}

// Activer les compteurs de caractères
setupCharacterCounter('projectDescription', 'descriptionCounter');
setupCharacterCounter('projectDescriptionLong', 'descriptionLongCounter');

// ==========================================
// IMAGE PREVIEW AMÉLIORÉE
// ==========================================

// Preview pour l'image principale
const projectImageInput = document.getElementById('projectImage');
const imagePreviewDiv = document.getElementById('imagePreview');

if (projectImageInput && imagePreviewDiv) {
    projectImageInput.addEventListener('change', function(e) {
        const file = e.target.files[0];

        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();

            reader.onload = function(event) {
                const img = imagePreviewDiv.querySelector('img');
                if (img) {
                    img.src = event.target.result;
                }
                imagePreviewDiv.style.display = 'block';
            };

            reader.readAsDataURL(file);
        } else {
            imagePreviewDiv.style.display = 'none';
        }
    });
}

// Preview pour la galerie
const galleryInput = document.getElementById('projectGallery');
const galleryPreviewDiv = document.getElementById('galleryPreview');

if (galleryInput && galleryPreviewDiv) {
    galleryInput.addEventListener('change', function(e) {
        galleryPreviewDiv.innerHTML = ''; // Vider les previews existantes

        const files = Array.from(e.target.files);

        files.forEach(file => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();

                reader.onload = function(event) {
                    const img = document.createElement('img');
                    img.src = event.target.result;
                    img.alt = 'Preview';
                    galleryPreviewDiv.appendChild(img);
                };

                reader.readAsDataURL(file);
            }
        });
    });
}

// ==========================================
// CONFIRMATION DE SUPPRESSION
// ==========================================
document.querySelectorAll('.btn-delete, .delete-btn, [class*="delete"]').forEach(btn => {
    // Éviter les doublons si le bouton a déjà un listener
    if (btn.dataset.confirmAdded) return;

    btn.addEventListener('click', function(e) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ?')) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    });

    btn.dataset.confirmAdded = 'true';
});

// ==========================================
// AUTO-SAVE INDICATOR
// ==========================================
function showSaveIndicator(success = true) {
    const indicator = document.createElement('div');
    indicator.className = 'save-indicator';
    indicator.textContent = success ? '✓ Sauvegardé' : '✗ Erreur';
    indicator.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 10px 20px;
        background: ${success ? '#27ae60' : '#e74c3c'};
        color: white;
        border-radius: 5px;
        font-size: 14px;
        z-index: 10000;
        animation: fadeInOut 2s ease-in-out;
    `;

    document.body.appendChild(indicator);

    setTimeout(() => {
        indicator.remove();
    }, 2000);
}

// Ajouter l'animation
if (!document.getElementById('dashboard-animations')) {
    const style = document.createElement('style');
    style.id = 'dashboard-animations';
    style.textContent = `
        @keyframes fadeInOut {
            0%, 100% { opacity: 0; transform: translateY(20px); }
            10%, 90% { opacity: 1; transform: translateY(0); }
        }

        .modal {
            animation: modalFadeIn 0.3s ease-out;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .modal-content {
            animation: modalSlideDown 0.3s ease-out;
        }

        @keyframes modalSlideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    `;
    document.head.appendChild(style);
}

// ==========================================
// UTILITAIRES
// ==========================================

// Formater les nombres
function formatNumber(num) {
    return new Intl.NumberFormat('fr-FR').format(num);
}

// Copier dans le presse-papier
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showSaveIndicator(true);
    });
}

// ==========================================
// GESTION DES MESSAGES
// ==========================================

// Marquer un message comme lu
async function markAsRead(messageId) {
    try {
        const formData = new FormData();
        addCsrfToken(formData);

        const response = await fetch(`${ADMIN_URL}/contacts/${messageId}/mark-read`, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            // Trouver l'élément du message et le marquer comme lu
            const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
            if (messageElement) {
                messageElement.classList.remove('unread');
                messageElement.classList.add('read');
                
                // Retirer le badge "Nouveau"
                const unreadBadge = messageElement.querySelector('.unread-badge');
                if (unreadBadge) {
                    unreadBadge.remove();
                }

                // Désactiver le bouton "Marquer comme lu"
                const markReadBtn = messageElement.querySelector('[onclick*="markAsRead"]');
                if (markReadBtn) {
                    markReadBtn.style.display = 'none';
                }
            }
            
            showSaveIndicator(true, 'Message marqué comme lu');
        } else {
            showSaveIndicator(false, result.message || 'Erreur lors du marquage');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showSaveIndicator(false, 'Erreur lors du marquage');
    }
}

// Supprimer un message
async function deleteMessage(messageId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce message ?')) {
        return;
    }

    try {
        const formData = new FormData();
        addCsrfToken(formData);

        const response = await fetch(`${ADMIN_URL}/contacts/${messageId}/delete`, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            // Retirer l'élément du DOM avec animation
            const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
            if (messageElement) {
                messageElement.style.opacity = '0';
                messageElement.style.transform = 'translateX(-100%)';
                setTimeout(() => {
                    messageElement.remove();
                    
                    // Vérifier s'il reste des messages
                    const messagesContainer = document.querySelector('.messages-container');
                    if (messagesContainer && messagesContainer.children.length === 0) {
                        location.reload(); // Recharger pour afficher l'état vide
                    }
                }, 300);
            }
            
            showSaveIndicator(true, 'Message supprimé');
        } else {
            showSaveIndicator(false, result.message || 'Erreur lors de la suppression');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showSaveIndicator(false, 'Erreur lors de la suppression');
    }
}

// ==========================================
// CUSTOM FILE INPUT - TRIGGER CLICK
// ==========================================

// Gérer les clics sur les labels customisés d'upload de fichiers
document.querySelectorAll('.file-input-wrapper').forEach(wrapper => {
    const input = wrapper.querySelector('input[type="file"]');
    const label = wrapper.querySelector('.file-input-label');

    if (input && label) {
        // Déclencher le sélecteur de fichiers quand on clique sur le label
        label.addEventListener('click', function() {
            input.click();
        });

        // Mettre à jour le texte du label quand un fichier est sélectionné
        input.addEventListener('change', function() {
            const fileCount = this.files.length;
            const labelSpan = label.querySelector('span');

            if (fileCount > 0) {
                if (this.multiple) {
                    labelSpan.textContent = `${fileCount} fichier(s) sélectionné(s)`;
                } else {
                    labelSpan.textContent = this.files[0].name;
                }
                label.style.borderColor = '#4caf50';
                label.style.color = '#4caf50';
            }
        });
    }
});

