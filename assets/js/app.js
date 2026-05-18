document.addEventListener('DOMContentLoaded', () => {
    const typeSelect = document.querySelector('[data-post-filter-type]');
    const searchForm = document.querySelector('[data-live-search-form]');

    if (typeSelect && searchForm) {
        typeSelect.addEventListener('change', () => searchForm.submit());
    }

    document.querySelectorAll('[data-auto-submit]').forEach((element) => {
        element.addEventListener('change', () => {
            const form = element.closest('form');
            if (form) {
                form.submit();
            }
        });
    });

    const communityChat = document.querySelector('[data-community-chat]');
    if (communityChat) {
        communityChat.scrollTop = communityChat.scrollHeight;
    }

    // Hide Page Loader
    const loader = document.getElementById('page-loader');
    if (loader) {
        setTimeout(() => {
            loader.classList.add('hide');
        }, 300); // Mượt mà hơn chút xíu
    }

    // Intersection Observer cho Scroll Animation
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };
    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    document.querySelectorAll('.animate-on-scroll').forEach(el => {
        observer.observe(el);
    });

    // Theme Switcher Logic
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        const updateThemeToggleUI = (currentTheme) => {
            if (currentTheme === 'dark') {
                themeToggle.innerHTML = '<i class="bi bi-sun me-2"></i> Chế độ sáng';
            } else {
                themeToggle.innerHTML = '<i class="bi bi-moon-stars me-2"></i> Chế độ tối';
            }
        };

        const activeTheme = document.documentElement.getAttribute('data-theme') || 'light';
        updateThemeToggleUI(activeTheme);

        themeToggle.addEventListener('click', (e) => {
            e.preventDefault();
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeToggleUI(newTheme);
        });
    }

    // Initialize all social and post interactions
    initializePostInteractions(document);

    // Infinite Scroll Implementation
    const postsContainer = document.getElementById('posts-container');
    const postsLoader = document.getElementById('posts-loader');
    
    if (postsContainer && postsLoader) {
        let offset = 3; // Initially 3 posts are loaded in PHP
        const limit = 3;
        let hasMore = true;
        let isLoading = false;

        const loadMorePosts = () => {
            if (!hasMore || isLoading) return;
            isLoading = true;
            postsLoader.classList.remove('d-none');

            fetch(`${window.clubitConfig.baseUrl}/api/posts.php?offset=${offset}&limit=${limit}`)
                .then(res => res.json())
                .then(data => {
                    postsLoader.classList.add('d-none');
                    if (data.success) {
                        const posts = data.posts;
                        if (posts.length === 0) {
                            hasMore = false;
                            return;
                        }

                        posts.forEach(post => {
                            const postCard = document.createElement('div');
                            postCard.className = 'clubit-card p-4 card-hover fly-up-item mb-4';

                            // Check if author has avatar
                            let avatarHtml = '';
                            if (post.author_avatar_url) {
                                avatarHtml = `<img src="${post.author_avatar_url}" class="rounded-circle border border-2 border-primary border-opacity-25" width="48" height="48" alt="Avatar" style="object-fit: cover;">`;
                            } else {
                                const initial = post.author_name.charAt(0).toUpperCase();
                                avatarHtml = `<div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width: 48px; height: 48px; font-size: 1.2rem;">${initial}</div>`;
                            }

                            // Check if post has multiple images
                            let postMediaHtml = '';
                            if (post.images && post.images.length > 0) {
                                const imgCount = post.images.length;
                                const minCount = Math.min(4, imgCount);
                                let itemsHtml = '';
                                for (let i = 0; i < minCount; i++) {
                                    const imgUrl = `${window.clubitConfig.baseUrl}/uploads/${post.images[i]}`;
                                    itemsHtml += `
                                        <div class="grid-item position-relative">
                                            <img src="${imgUrl}" class="w-100 h-100" style="object-fit: cover; max-height: ${imgCount === 1 ? '400px' : (imgCount === 2 ? '280px' : '200px')};" alt="Post image">
                                            ${i === 3 && imgCount > 4 ? `
                                                <div class="grid-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-black bg-opacity-60 text-white fw-bold fs-4">
                                                    +${imgCount - 4}
                                                </div>
                                            ` : ''}
                                        </div>
                                    `;
                                }
                                postMediaHtml = `
                                    <a href="${window.clubitConfig.baseUrl}/post.php?id=${post.id}" class="d-block mb-3 post-media-grid grid-${minCount}">
                                        ${itemsHtml}
                                    </a>
                                `;
                            } else if (post.image_url) {
                                postMediaHtml = `
                                    <a href="${window.clubitConfig.baseUrl}/post.php?id=${post.id}" class="d-block mb-3">
                                        <img src="${post.image_url}" class="img-fluid rounded-3 w-100" style="max-height: 400px; object-fit: cover;" alt="${post.title}">
                                    </a>
                                `;
                            }

                            // Check if post has files
                            let postFilesHtml = '';
                            if (post.files && post.files.length > 0) {
                                let filesListHtml = '';
                                post.files.forEach(f => {
                                    const ext = (f.type || 'file').toLowerCase();
                                    let iconClass = 'bi-file-earmark';
                                    let iconColor = 'text-secondary';

                                    if (ext === 'pdf') { iconClass = 'bi-file-earmark-pdf-fill'; iconColor = 'text-danger'; }
                                    else if (['doc', 'docx'].includes(ext)) { iconClass = 'bi-file-earmark-word-fill'; iconColor = 'text-primary'; }
                                    else if (['ppt', 'pptx'].includes(ext)) { iconClass = 'bi-file-earmark-slides-fill'; iconColor = 'text-warning'; }
                                    else if (['xls', 'xlsx'].includes(ext)) { iconClass = 'bi-file-earmark-excel-fill'; iconColor = 'text-success'; }
                                    else if (['zip', 'rar'].includes(ext)) { iconClass = 'bi-file-earmark-zip-fill'; iconColor = 'text-info'; }

                                    filesListHtml += `
                                        <div class="d-flex align-items-center justify-content-between p-2 rounded-3 border border-white border-opacity-5" style="background: rgba(0, 0, 0, 0.15);">
                                            <div class="d-flex align-items-center gap-2 overflow-hidden">
                                                <i class="bi ${iconClass} ${iconColor} fs-4"></i>
                                                <div class="overflow-hidden">
                                                    <div class="text-white text-truncate fw-semibold small" style="max-width: 250px;">${f.name}</div>
                                                    <div class="text-muted" style="font-size: 0.7rem;">${f.size || 'N/A'}</div>
                                                </div>
                                            </div>
                                            <a href="${window.clubitConfig.baseUrl}/uploads/${f.path}" download="${f.name}" class="btn btn-sm btn-primary py-1 px-3 rounded-pill fw-semibold fs-7 d-flex align-items-center gap-1">
                                                <i class="bi bi-download"></i> Tải về
                                            </a>
                                        </div>
                                    `;
                                });

                                postFilesHtml = `
                                    <div class="post-attachments p-3 rounded-4 border border-white border-opacity-10 mb-3" style="background: rgba(255, 255, 255, 0.02);">
                                        <div class="small text-white-50 fw-semibold mb-2"><i class="bi bi-paperclip"></i> Tài liệu đính kèm:</div>
                                        <div class="d-flex flex-column gap-2">
                                            ${filesListHtml}
                                        </div>
                                    </div>
                                `;
                            }

                            // Comments section comment form or login prompt
                            let commentFormHtml = '';
                            if (window.clubitConfig.isLoggedIn) {
                                commentFormHtml = `
                                    <form class="form-inline-comment d-flex gap-2 align-items-center" data-post-id="${post.id}">
                                        <img src="${window.clubitConfig.userAvatar}" class="rounded-circle" width="32" height="32" alt="Avatar" style="object-fit: cover;">
                                        <input type="text" name="content" class="form-control form-control-sm text-white rounded-pill flex-grow-1" style="background: rgba(255, 255, 255, 0.05); border: 1px solid var(--clubit-border);" placeholder="Viết bình luận..." required>
                                        <button type="submit" class="btn btn-sm btn-primary rounded-circle" style="width: 32px; height: 32px; padding: 0;"><i class="bi bi-send"></i></button>
                                    </form>
                                `;
                            } else {
                                commentFormHtml = `
                                    <div class="text-center text-muted small"><a href="${window.clubitConfig.baseUrl}/login.php" class="text-primary text-decoration-none">Đăng nhập</a> để bình luận.</div>
                                `;
                            }

                            postCard.innerHTML = `
                                <!-- Post Header -->
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="d-flex align-items-center gap-3">
                                        ${avatarHtml}
                                        <div>
                                            <div class="fw-bold text-white fs-6">${post.author_name}</div>
                                            <div class="small text-secondary">
                                                <i class="bi bi-clock"></i> ${post.formatted_date}
                                                · ${post.privacy === 'private' ? '<i class="bi bi-lock-fill text-warning" title="Riêng tư"></i>' : '<i class="bi bi-globe-americas text-primary" title="Công khai"></i>'}
                                            </div>
                                        </div>
                                    </div>
                                    <span class="soft-badge">${post.category_name}</span>
                                </div>

                                <!-- Post Body -->
                                <h5 class="fw-bold mb-2">
                                    <a href="${window.clubitConfig.baseUrl}/post.php?id=${post.id}" class="text-white text-decoration-none hover-underline">${post.title}</a>
                                </h5>
                                <p class="text-white-50 mb-3">${post.excerpt}</p>

                                ${postMediaHtml}
                                ${postFilesHtml}

                                <!-- Post Actions -->
                                <div class="border-top pt-3 border-opacity-10 border-white d-flex justify-content-between">
                                    <button type="button" class="btn btn-sm text-white-50 fw-semibold px-4 btn-reaction" data-post-id="${post.id}">
                                        <span class="reaction-icon"><i class="bi bi-hand-thumbs-up"></i></span> 
                                        <span class="reaction-text">Thích</span>
                                        <span class="reaction-count ms-1"></span>
                                    </button>
                                    <button type="button" class="btn btn-sm text-white-50 fw-semibold px-4 btn-toggle-comments" data-post-id="${post.id}">
                                        <i class="bi bi-chat-dots"></i> Bình luận
                                    </button>
                                    <button type="button" class="btn btn-sm text-white-50 fw-semibold px-4" onclick="navigator.clipboard.writeText('${window.clubitConfig.baseUrl}/post.php?id=${post.id}'); alert('Đã copy link!');"><i class="bi bi-share"></i> Chia sẻ</button>
                                </div>

                                <!-- Inline Comments Section -->
                                <div class="inline-comments-section mt-3" id="inline-comments-${post.id}" style="display: none;">
                                    <div class="comments-list mb-3 d-grid gap-2"></div>
                                    <div class="text-center mb-2" style="display: none;"><button class="btn btn-sm btn-link text-white-50 text-decoration-none btn-load-more-comments" data-post-id="${post.id}" data-offset="0">Xem thêm bình luận</button></div>
                                    ${commentFormHtml}
                                </div>
                            `;

                            postsContainer.appendChild(postCard);
                            initializePostInteractions(postCard);
                        });

                        offset += posts.length;
                        hasMore = data.has_more;
                    }
                    isLoading = false;
                })
                .catch(err => {
                    console.error(err);
                    isLoading = false;
                    postsLoader.classList.add('d-none');
                });
        };

        // Scroll listener with simple throttle
        let throttleTimer;
        window.addEventListener('scroll', () => {
            if (throttleTimer) return;
            throttleTimer = setTimeout(() => {
                throttleTimer = null;
                if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 300) {
                    loadMorePosts();
                }
            }, 100);
        });
    }
});

/* --------------------------------------------------------------------------
 * 1. POST INTERACTION INITIALIZATION (EMOJI, INLINE COMMENTS)
 * -------------------------------------------------------------------------- */
function initializePostInteractions(container = document) {
    // A. EMOJI REACTIONS (Long Press & Drag Select)
    container.querySelectorAll('.btn-reaction').forEach(btn => {
        if (btn.dataset.initialized) return;
        btn.dataset.initialized = 'true';

        let pressTimer;
        const postId = btn.dataset.postId;
        
        let popup = btn.querySelector('.emoji-popup');
        if (!popup) {
            popup = document.createElement('div');
            popup.className = 'emoji-popup';
            popup.innerHTML = `
                <span class="emoji-option" data-emoji="👍">👍</span>
                <span class="emoji-option" data-emoji="❤️">❤️</span>
                <span class="emoji-option" data-emoji="😂">😂</span>
                <span class="emoji-option" data-emoji="😮">😮</span>
                <span class="emoji-option" data-emoji="😢">😢</span>
                <span class="emoji-option" data-emoji="😡">😡</span>
            `;
            btn.style.position = 'relative';
            btn.appendChild(popup);
            
            popup.querySelectorAll('.emoji-option').forEach(opt => {
                opt.addEventListener('click', (e) => {
                    e.stopPropagation();
                    e.preventDefault();
                    submitReaction(postId, opt.dataset.emoji, btn);
                    popup.classList.remove('show');
                });
            });
        }

        let isDragging = false;
        let wasDragging = false;
        let hasMoved = false;
        let lastTouchX = 0;
        let lastTouchY = 0;

        const handleMove = (e) => {
            if (!isDragging) return;
            hasMoved = true;
            let clientX = 0;
            let clientY = 0;
            if (e.type === 'mousemove') {
                clientX = e.clientX;
                clientY = e.clientY;
            } else if (e.type === 'touchmove') {
                clientX = e.touches[0].clientX;
                clientY = e.touches[0].clientY;
                lastTouchX = clientX;
                lastTouchY = clientY;
                if (e.cancelable) {
                    e.preventDefault();
                }
            }

            const element = document.elementFromPoint(clientX, clientY);
            const opt = element ? element.closest('.emoji-option') : null;
            
            popup.querySelectorAll('.emoji-option').forEach(el => {
                if (opt && el === opt) {
                    el.classList.add('active-drag');
                } else {
                    el.classList.remove('active-drag');
                }
            });
        };

        const globalRelease = (e) => {
            cancelPress(e);
        };

        const startPress = (e) => {
            if (e.type === 'click' && e.detail === 0) return;
            isDragging = false;
            wasDragging = false;
            hasMoved = false;
            if (e.touches && e.touches.length > 0) {
                lastTouchX = e.touches[0].clientX;
                lastTouchY = e.touches[0].clientY;
            }
            pressTimer = window.setTimeout(() => {
                document.querySelectorAll('.emoji-popup.show').forEach(p => p.classList.remove('show'));
                popup.classList.add('show');
                isDragging = true;
                
                window.addEventListener('mousemove', handleMove);
                window.addEventListener('mouseup', globalRelease);
                btn.addEventListener('touchmove', handleMove, {passive: false});
            }, 300);
        };

        const cancelPress = (e) => {
            clearTimeout(pressTimer);
            if (isDragging) {
                isDragging = false;
                wasDragging = true;
                setTimeout(() => { wasDragging = false; }, 100);
                
                window.removeEventListener('mousemove', handleMove);
                window.removeEventListener('mouseup', globalRelease);
                btn.removeEventListener('touchmove', handleMove);
                
                if (hasMoved) {
                    const activeOpt = popup.querySelector('.emoji-option.active-drag');
                    if (activeOpt) {
                        submitReaction(postId, activeOpt.dataset.emoji, btn);
                    }
                    popup.classList.remove('show');
                }
                
                popup.querySelectorAll('.emoji-option').forEach(el => el.classList.remove('active-drag'));
                
                if (e.cancelable) {
                    e.preventDefault();
                }
                e.stopPropagation();
            }
        };

        btn.addEventListener('mousedown', startPress);
        btn.addEventListener('mouseup', cancelPress);
        btn.addEventListener('mouseleave', cancelPress);
        btn.addEventListener('touchstart', startPress, {passive: false});
        btn.addEventListener('touchend', cancelPress);
        btn.addEventListener('touchcancel', cancelPress);
        btn.addEventListener('dragstart', (e) => e.preventDefault());

        btn.addEventListener('click', (e) => {
            if (wasDragging) {
                e.preventDefault();
                e.stopPropagation();
                return;
            }
            if (popup.classList.contains('show')) {
                popup.classList.remove('show');
                return;
            }
            submitReaction(postId, '👍', btn);
        });
    });

    // B. INLINE COMMENTS TOGGLERS
    container.querySelectorAll('.btn-toggle-comments').forEach(btn => {
        if (btn.dataset.initialized) return;
        btn.dataset.initialized = 'true';

        btn.addEventListener('click', () => {
            const postId = btn.dataset.postId;
            const section = document.getElementById(`inline-comments-${postId}`);
            
            if (section.style.display === 'none') {
                section.style.display = 'block';
                loadComments(postId, 0, section);
            } else {
                section.style.display = 'none';
            }
        });
    });

    // C. LOAD MORE COMMENTS
    container.querySelectorAll('.btn-load-more-comments').forEach(btn => {
        if (btn.dataset.initialized) return;
        btn.dataset.initialized = 'true';

        btn.addEventListener('click', () => {
            const postId = btn.dataset.postId;
            const offset = parseInt(btn.dataset.offset);
            const section = document.getElementById(`inline-comments-${postId}`);
            loadComments(postId, offset, section);
        });
    });

    // D. INLINE COMMENT FORM SUBMIT
    container.querySelectorAll('.form-inline-comment').forEach(form => {
        if (form.dataset.initialized) return;
        form.dataset.initialized = 'true';

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const postId = form.dataset.postId;
            const input = form.querySelector('input[name="content"]');
            const content = input.value.trim();
            
            if (!content) return;
            
            fetch('/clbIT/api/comments.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ post_id: postId, content: content })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    const list = document.querySelector(`#inline-comments-${postId} .comments-list`);
                    const c = data.comment;
                    list.innerHTML = `
                        <div class="d-flex gap-2 align-items-start">
                            <img src="${c.avatar}" class="rounded-circle mt-1" width="32" height="32" alt="Avatar" style="object-fit: cover;">
                            <div class="bg-dark text-white rounded-3 p-2 px-3" style="background: rgba(255, 255, 255, 0.05) !important;">
                                <div class="fw-bold fs-6">${c.fullname}</div>
                                <div class="small">${c.content}</div>
                            </div>
                        </div>
                    ` + list.innerHTML;
                } else {
                    alert(data.error);
                }
            });
        });
    });

    // Fetch initial reactions for the newly initialized items
    const newPostIds = Array.from(container.querySelectorAll('.btn-reaction'))
        .filter(b => !b.dataset.reactionsLoaded)
        .map(b => {
            b.dataset.reactionsLoaded = 'true';
            return b.dataset.postId;
        })
        .join(',');
        
    if (newPostIds) {
        fetch(`/clbIT/api/reactions.php?post_ids=${newPostIds}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                container.querySelectorAll('.btn-reaction').forEach(btn => {
                    const pid = btn.dataset.postId;
                    if (data.reactions && data.reactions[pid]) {
                        updateReactionUI(btn, data.reactions[pid].user_emoji, data.reactions[pid].total);
                    }
                });
            }
        });
    }
}

document.addEventListener('click', (e) => {
    if (!e.target.closest('.btn-reaction')) {
        document.querySelectorAll('.emoji-popup.show').forEach(p => p.classList.remove('show'));
    }
});

function submitReaction(postId, emoji, btn) {
    fetch('/clbIT/api/reactions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ post_id: postId, emoji: emoji })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            updateReactionUI(btn, data.user_emoji, data.total);
        } else {
            if (data.error === 'Vui lòng đăng nhập') {
                window.location.href = '/clbIT/login.php';
            } else {
                alert(data.error);
            }
        }
    })
    .catch(err => console.error(err));
}

function updateReactionUI(btn, userEmoji, total) {
    const iconSpan = btn.querySelector('.reaction-icon');
    const textSpan = btn.querySelector('.reaction-text');
    const countSpan = btn.querySelector('.reaction-count');
    
    if (userEmoji) {
        iconSpan.innerHTML = userEmoji;
        textSpan.innerHTML = '';
        btn.classList.add('reacted', 'text-primary');
        btn.classList.remove('text-white-50');
    } else {
        iconSpan.innerHTML = '<i class="bi bi-hand-thumbs-up"></i>';
        textSpan.innerHTML = 'Thích';
        btn.classList.remove('reacted', 'text-primary');
        btn.classList.add('text-white-50');
    }
    
    countSpan.textContent = total > 0 ? total : '';
}

function loadComments(postId, offset, section) {
    fetch(`/clbIT/api/comments.php?post_id=${postId}&offset=${offset}&limit=5`)
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const list = section.querySelector('.comments-list');
            if (offset === 0) list.innerHTML = '';
            
            data.comments.forEach(c => {
                list.innerHTML += `
                    <div class="d-flex gap-2 align-items-start">
                        <img src="${c.avatar}" class="rounded-circle mt-1" width="32" height="32" alt="Avatar" style="object-fit: cover;">
                        <div class="bg-dark text-white rounded-3 p-2 px-3" style="background: rgba(255, 255, 255, 0.05) !important;">
                            <div class="fw-bold fs-6">${c.fullname}</div>
                            <div class="small">${c.content}</div>
                        </div>
                    </div>
                `;
            });
            
            const btnMore = section.querySelector('.btn-load-more-comments');
            if (data.has_more) {
                btnMore.dataset.offset = offset + 5;
                btnMore.parentElement.style.display = 'block';
            } else {
                btnMore.parentElement.style.display = 'none';
            }
        }
    });
}

/* --------------------------------------------------------------------------
 * 3. REALTIME COMMUNITY CHAT
 * -------------------------------------------------------------------------- */
const chatContainer = document.getElementById('community-chat-container');
const chatForm = document.getElementById('form-chat');
let chatPollingTimer;

if (chatContainer) {
    // Scroll to bottom initially
    chatContainer.scrollTop = chatContainer.scrollHeight;
    
    // Submit form via AJAX
    if (chatForm) {
        chatForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const input = chatForm.querySelector('textarea[name="content"]');
            const content = input.value.trim();
            if (!content) return;
            
            fetch('/clbIT/api/community.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ content: content })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    appendMessage(data.message);
                    chatContainer.dataset.lastId = data.message.id;
                } else {
                    alert(data.error);
                }
            });
        });
        
        // Submit on Enter
        const input = chatForm.querySelector('textarea[name="content"]');
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                chatForm.dispatchEvent(new Event('submit'));
            }
        });
    }

    // Polling every 3 seconds
    const pollMessages = () => {
        const lastId = chatContainer.dataset.lastId || 0;
        fetch(`/clbIT/api/community.php?after_id=${lastId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.messages.length > 0) {
                // Remove empty state if exists
                const emptyState = chatContainer.querySelector('.community-empty-state');
                if (emptyState) emptyState.remove();
                
                chatContainer.classList.remove('is-empty');
                
                let isScrolledToBottom = chatContainer.scrollHeight - chatContainer.clientHeight <= chatContainer.scrollTop + 50;
                
                data.messages.forEach(msg => {
                    appendMessage(msg);
                    chatContainer.dataset.lastId = msg.id;
                });
                
                if (isScrolledToBottom) {
                    chatContainer.scrollTop = chatContainer.scrollHeight;
                }
            }
        })
        .finally(() => {
            chatPollingTimer = setTimeout(pollMessages, 3000);
        });
    };
    
    chatPollingTimer = setTimeout(pollMessages, 3000);
}

function appendMessage(m) {
    const isMine = m.is_mine;
    const adminBadge = m.role === 'admin' ? ' <span class="badge text-bg-info ms-1">Admin</span>' : '';
    
    // Replace newlines with <br> manually to prevent XSS from innerHTML (mostly)
    const contentSafe = m.content.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/\n/g, "<br>");
    const fullnameSafe = m.fullname.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
    
    const html = `
        <div class="community-message ${isMine ? 'is-mine ms-auto' : ''}">
            <div class="d-flex align-items-start gap-3">
                <a href="/clbIT/profile.php?id=${m.user_id}" class="text-decoration-none">
                    <img src="${m.avatar}" alt="${fullnameSafe}" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;">
                </a>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-1">
                        <div class="fw-bold text-white">
                            <a href="/clbIT/profile.php?id=${m.user_id}" class="text-white text-decoration-none">
                                ${fullnameSafe}
                            </a>
                            ${adminBadge}
                        </div>
                        <div class="small text-white-50">${m.created_at}</div>
                    </div>
                    <div class="text-white">${contentSafe}</div>
                </div>
                ${!isMine ? `<img src="${m.avatar}" alt="${fullnameSafe}" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;visibility:hidden;">` : ''}
            </div>
        </div>
    `;
    chatContainer.insertAdjacentHTML('beforeend', html);
}
