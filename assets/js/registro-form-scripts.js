document.addEventListener('DOMContentLoaded', function() { 
    mountMediaUploaders();
    mountDeletableAttachmentButtons();
    mountFilePreviewModal();    
    mountLogPreviewModal();
    mountCommentForm();
});

/**
 * Monta lógica da galeria de mídias do WordPress
 */
function mountMediaUploaders() {

    document.querySelectorAll('.file-input').forEach(function(fileInput) {
        fileInput.onchange = () => {

            if (fileInput.files.length > 0) {
                
                fileInput.classList.add('is-loading');
                fileInput.disabled = true;

                const file = fileInput.files[0];
                let target = fileInput.getAttribute('data-target');
                let label = fileInput.getAttribute('data-target-label');
                let postId = fileInput.getAttribute('data-post-id');

                // Faz o upload do arquivo para o endpoint de mídias do wordpress
                let formData = new FormData();
                formData.append('file', file);
                formData.append('title', file.name);
            
                fetch('/wp-json/wp/v2/media?post=' + postId, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Disposition': 'attachment; filename=' + file.name,
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-WP-Nonce': registro_script_settings.wp_nonce   
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(attachment => {
                    if (attachment.message) {
                        alert('Ocorreu um erro ao fazer o upload do arquivo: ' + attachment.message);
                        fileInput.classList.remove('is-loading');
                        fileInput.disabled = false;
                        return;
                    }

                    if (attachment.mime_type !== 'application/pdf') {
                        alert('Você só pode fazer o envio de arquivos em formato PDF. Por favor tente novamente.');
                        fileInput.classList.remove('is-loading');
                        fileInput.disabled = false;
                        return;
                    }

                    // Makes a post to the post meta endpoint to update the post meta of key target with the attachment id
                    let body = { 'meta': {} }
                    body.meta[target] = attachment.id;
                
                    fetch('/wp-json/wp/v2/registro/' + postId, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': registro_script_settings.wp_nonce   
                        },
                        body: JSON.stringify(body)
                    })
                    .then(response => response.json())
                    .then(() => {
                    
                        document.getElementById(target).value = attachment.id;

                        let mediaWrapper = document.getElementById(target).nextElementSibling;
                        if (mediaWrapper && mediaWrapper.classList.contains('media')) {
                            // Remove conteúdo atual, que contém o botão de upload
                            mediaWrapper.innerHTML = '';

                            // Cria os elementos wrapper
                            let imageParagraph = document.createElement('p');
                            imageParagraph.classList.add('image');

                            let mediaContent = document.createElement('div');
                            mediaContent.classList.add('media-content');

                            let buttonsContainer = document.createElement('div');
                            buttonsContainer.classList.add('attachment-edit-buttons');

                            let contentDiv = document.createElement('div');
                            contentDiv.classList.add('content', 'attachment-edit-buttons');

                            // Adiciona o botão de ver arquivo
                            let linkButton = document.createElement('button');
                            linkButton.type = 'button';
                            linkButton.classList.add('file-preview-modal-trigger', 'button');
                            linkButton.setAttribute('data-target', 'file-preview-modal');
                            linkButton.setAttribute('data-file-url', registro_script_settings.theme_uri + '/inc/registro/registro-serve-file.php?file_id=' + attachment.id);
                            linkButton.setAttribute('data-file-field', label);
                            linkButton.title = attachment.title && attachment.title.rendered ? attachment.title.rendered : attachment.source_url;
                            linkButton.innerHTML = `
                                <span class="icon">
                                    <i>
                                        <svg width="18" xmlns="http://www.w3.org/2000/svg" height="18" viewBox="2885 -149.35 18 18" fill="none">
                                            <path d="m2898.013-149.35-3.532 3.526 1.076 1.073 2.456-2.452 3.838 3.831-6.035 6.026-2.548-2.544-1.074 1.073 3.622 3.617 8.184-8.172Zm-4.829 4.821-8.184 8.172 5.987 5.979 3.532-3.527-1.075-1.073-2.457 2.453-3.837-3.832 6.034-6.025 2.548 2.543 1.075-1.073Z" style="fill: rgb(0, 0, 0); fill-opacity: 1;" class="fills" data-testid="svg-path"/>
                                        </svg>
                                    </i>
                                </span>
                                <span>Ver arquivo</span>
                            `;
                            contentDiv.appendChild(linkButton);

                            // Adiciona o botão de remover
                            let removeButton = document.createElement('button');
                            removeButton.type = 'button';
                            removeButton.setAttribute('data-target', target);
                            removeButton.setAttribute('data-post-id', postId);
                            removeButton.classList.add('delete-file-button', 'button', 'is-danger', 'is-light');
                            removeButton.innerHTML = `<span class="icon">
                                    <i>
                                        <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24" fill="currentColor">
                                            <path d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                        </svg>
                                    </i>
                                </span>
                                <span>Deletar</span>`;
                            contentDiv.appendChild(removeButton);

                            mediaContent.appendChild(contentDiv);

                            // Adiciona o botão de substituir
                            let replaceInputWrapper = document.createElement('div');
                            replaceInputWrapper.classList.add('file', 'is-link', 'is-light');

                            let replaceInput = document.createElement('input');
                            replaceInput.setAttribute('type','file');
                            replaceInput.setAttribute('accept', 'application/pdf,.pdf');
                            replaceInput.classList.add('file-input');
                            replaceInput.setAttribute('data-target', target);
                            replaceInput.setAttribute('data-target-label', label);
                            replaceInput.setAttribute('data-post-id', postId);
                            replaceInput.setAttribute('name', target + '-file-input');

                            let replaceInputLabel = document.createElement('label');
                            replaceInputLabel.classList.add('file-label');
                            replaceInputLabel.style = 'width: 100%;';

                            let replaceInputSpan = document.createElement('span');
                            replaceInputSpan.classList.add('file-cta');
                            replaceInputSpan.innerHTML = `
                                <span class="file-icon icon">
                                    <i>
                                        <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24" fill="currentColor">
                                            <path d="M0 0h24v24H0z" fill="none" />
                                            <path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z" />
                                        </svg>
                                    </i>
                                </span>
                                <span class="file-label">Substituir arquivo... </span>
                            `;
                            replaceInputLabel.appendChild(replaceInputSpan);
                            replaceInputLabel.appendChild(replaceInput);
                            
                            replaceInputWrapper.appendChild(replaceInputLabel);

                            buttonsContainer.appendChild(replaceInputWrapper);
                            mediaContent.appendChild(buttonsContainer);

                            // Adiciona os elementos wrappers ao mediaWrapper
                            mediaWrapper.appendChild(mediaContent);

                            mountFilePreviewModal();
                            mountMediaUploaders();
                            mountDeletableAttachmentButtons();
                        }

                    });
                })
            }
          };
    });
}


/**
 * Monta lógica dos botões de remover anexos
 */
function mountDeletableAttachmentButtons() {
    var nonceField = document.querySelector('input[name="remove_attachment_nonce"]');

    document.querySelectorAll('.delete-file-button').forEach(function(removeButton) {
        removeButton.addEventListener('click', function(e) {
            e.preventDefault();

            var data = new FormData();

            const inputField = document.getElementById(removeButton.getAttribute('data-target'));
            removeButton.classList.add('is-loading');

            data.append('action', 'remove_attachment');
            data.append('post_id', removeButton.getAttribute('data-post-id'));
            data.append('field_id', removeButton.getAttribute('data-target'));
            data.append('attachment_id', inputField.value);
            data.append('_wpnonce', nonceField.value);

            fetch(registro_script_settings.ajaxurl, {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    inputField.value = '';

                    // Adiciona o botão de enviar novamente
                    let replaceInputWrapper = document.createElement('div');
                    replaceInputWrapper.classList.add('file', 'is-boxed', 'is-link', 'is-light');

                    let replaceInput = document.createElement('input');
                    replaceInput.setAttribute('type','file');
                    replaceInput.setAttribute('accept', 'application/pdf,.pdf');
                    replaceInput.classList.add('file-input');
                    replaceInput.setAttribute('data-target', removeButton.getAttribute('data-target'));
                    replaceInput.setAttribute('data-target-label', removeButton.getAttribute('data-target-label'));
                    replaceInput.setAttribute('data-post-id', removeButton.getAttribute('data-post-id'));
                    replaceInput.setAttribute('name', removeButton.getAttribute('data-target') + '-file-input');

                    let replaceInputLabel = document.createElement('label');
                    replaceInputLabel.classList.add('file-label');
                    replaceInputLabel.style = 'width: 100%;';

                    let replaceInputSpan = document.createElement('span');
                    replaceInputSpan.classList.add('file-cta');
                    replaceInputSpan.innerHTML = `
                        <span class="file-icon icon">
                            <i>
                                <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24" fill="currentColor">
                                    <path d="M0 0h24v24H0z" fill="none" />
                                    <path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z" />
                                </svg>
                            </i>
                        </span>
                        <span class="file-label">Escolha um arquivo... </span>
                    `;
                    replaceInputLabel.appendChild(replaceInputSpan);
                    replaceInputLabel.appendChild(replaceInput);
                    
                    replaceInputWrapper.appendChild(replaceInputLabel);

                    let mediaWrapper = inputField.nextElementSibling;
                    mediaWrapper.innerHTML = replaceInputWrapper.outerHTML;

                    mountFilePreviewModal();
                    mountMediaUploaders();
                    mountDeletableAttachmentButtons();

                } else {
                    alert(result.data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });
    
}

/**
 * Função com a lógica para invocoar o modal de visualização de arquivos
 */
function mountFilePreviewModal() {

    // Funções para abrir e fechar um modal
    function openModal(el, fileURL, fileField) {
        el.classList.add('is-active');
        document.getElementsByTagName('html')[0].classList.add('is-clipped');

        if ( fileURL && fileField ) {
            el.querySelector('.modal-card-body').innerHTML = `
                <h3>${fileField}</h3>
                <br>
                <embed src="${fileURL}" type="application/pdf" width="100%" height="90%">
            `;
        }
    }

    function closeModal(el) {
        document.getElementsByTagName('html')[0].classList.remove('is-clipped');
        el.classList.remove('is-active');
    }

    function closeAllModals() {
        document.querySelectorAll('.modal').forEach((modal) => {
            closeModal(modal);
        });
    }

    // Adiciona um evento de clique nos botões para abrir um modal específico
    document.querySelectorAll('.file-preview-modal-trigger').forEach((trigger) => {
        const modal = trigger.dataset.target;
        const target = document.getElementById(modal);
        const fileURL = trigger.dataset.fileUrl;
        const fileField = trigger.dataset.fileField;
        trigger.addEventListener('click', () => {
            openModal(target, fileURL, fileField);
        });
    });

    // Adiciona um evento de clique em vários elementos filhos para fechar o modal pai
    document.querySelectorAll('.modal-background, .modal-close, .modal-card-head .delete, .modal-card-foot .button').forEach((close) => {
        const target = close.closest('.modal');

        close.addEventListener('click', () => {
            closeModal(target);
        });
    });

    // Adiciona um evento de teclado para fechar todos os modais
    document.addEventListener('keydown', (event) => {
        if(event.key === "Escape") {
            closeAllModals();
        }
    });
}

/**
 * Função com a lógica para invocoar o modal de visualização de logs
 */
function mountLogPreviewModal() {

    // Funções para abrir e fechar um modal
    function openModal(el) {
        el.classList.add('is-active');
        document.getElementsByTagName('html')[0].classList.add('is-clipped');
    }

    function closeModal(el) {
        document.getElementsByTagName('html')[0].classList.remove('is-clipped');
        el.classList.remove('is-active');
    }

    function closeAllModals() {
        document.querySelectorAll('.modal').forEach((modal) => {
            closeModal(modal);
        });
    }

    // Adiciona um evento de clique nos botões para abrir um modal específico
    document.querySelectorAll('.log-preview-modal-trigger').forEach((trigger) => {
        const modal = trigger.dataset.target;
        const target = document.getElementById(modal);
        trigger.addEventListener('click', () => {
            openModal(target);
        });
    });

    // Adiciona um evento de clique em vários elementos filhos para fechar o modal pai
    document.querySelectorAll('.modal-background, .modal-close, .modal-card-head .delete, .modal-card-foot .button').forEach((close) => {
        const target = close.closest('.modal');

        close.addEventListener('click', () => {
            closeModal(target);
        });
    });

    // Adiciona um evento de teclado para fechar todos os modais
    document.addEventListener('keydown', (event) => {
        if(event.key === "Escape") {
            closeAllModals();
        }
    });
}


/**
 * Monta lógica do formulário de comentários
 */
function mountCommentForm() {
    var submitButton = document.querySelector('#submit-comment');
    var commentList = document.querySelector('.comment-list');
    var commentField = document.querySelector('#comment');
    var postIdField = document.querySelector('input[name="post_id"]');
    var nonceField = document.querySelector('input[name="comment_form_nonce"]');

    if ( !submitButton )
        return;

    submitButton.addEventListener('click', function(e) {
        e.preventDefault();

        if ( !commentField.value ) {
            alert('Por favor, preencha o campo de observação.');
            return;
        }

        submitButton.classList.add('is-loading');

        var data = new FormData();
        data.append('action', 'save_comment');
        data.append('comment', commentField.value);
        data.append('post_id', postIdField.value);
        data.append('_wpnonce', nonceField.value);

        fetch(registro_script_settings.ajaxurl, {
            method: 'POST',
            body: data
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {

                const emptyMessageWarning  = document.getElementById('empty-message-warning');

                if (emptyMessageWarning) 
                    emptyMessageWarning.remove();

                var newComment = document.createElement('li');
                newComment.classList.add('comment', 'media');
                newComment.innerHTML = `
                    <div class="comment-content">
                        ${result.data.content}
                        <p class="comment-author"><strong>${result.data.author}</strong></p>    
                    </div>
                    <p class="comment-date media-left"><em>${result.data.date}</em></p>
                `;
                commentList.appendChild(newComment);
                commentField.value = '';
                
                submitButton.classList.remove('is-loading');

            } else {
                alert(result.data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    });
    
}
