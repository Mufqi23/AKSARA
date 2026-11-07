(function () {
    // Password show/hide toggle (works anywhere including inside pop-up modals)
    document.addEventListener('click', event => {
        const btn = event.target.closest('[data-toggle-pass]');
        if (!btn) return;
        const wrap = btn.closest('.pass-input-wrap, .login-input-wrap, .uiv-inputBox');
        const input = (wrap && wrap.querySelector('input')) || document.getElementById(btn.getAttribute('data-toggle-pass'));
        if (!input) return;
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        const openIcon = btn.querySelector('.eye-open');
        const closedIcon = btn.querySelector('.eye-closed');
        if (openIcon) openIcon.style.display = show ? 'none' : '';
        if (closedIcon) closedIcon.style.display = show ? '' : 'none';
    });

    // Universal Pop-Up Modals for Delete & Edit across all user roles
    const deleteModal = document.getElementById('aksaraDeleteModal');
    const deleteMessage = document.getElementById('aksaraDeleteMessage');
    const deleteConfirmBtn = document.getElementById('aksaraDeleteConfirmBtn');
    const editModal = document.getElementById('aksaraEditModal');
    const editTitle = document.getElementById('aksaraEditTitle');
    const editBody = document.getElementById('aksaraEditBody');
    let pendingDeleteForm = null;

    function closePopups() {
        if (deleteModal) {
            deleteModal.classList.remove('is-open');
            deleteModal.setAttribute('aria-hidden', 'true');
        }
        if (editModal) {
            editModal.classList.remove('is-open');
            editModal.setAttribute('aria-hidden', 'true');
        }
        pendingDeleteForm = null;
    }

    document.addEventListener('click', event => {
        if (event.target.closest('[data-popup-close]') || event.target === deleteModal || event.target === editModal) {
            event.preventDefault();
            closePopups();
            return;
        }

        // Inline Edit Pop-Up trigger ([data-popup-target])
        const targetBtn = event.target.closest('[data-popup-target]');
        if (targetBtn && editModal && editBody) {
            event.preventDefault();
            const source = document.querySelector(targetBtn.getAttribute('data-popup-target'));
            if (!source) return;
            if (editTitle) editTitle.textContent = targetBtn.getAttribute('data-popup-title') || 'Ubah Data';
            editBody.innerHTML = source.innerHTML;
            editModal.classList.add('is-open');
            editModal.setAttribute('aria-hidden', 'false');
            const firstInput = editBody.querySelector('input:not([type="hidden"]), select, textarea');
            if (firstInput) firstInput.focus();
            return;
        }

        // Remote Edit Link Pop-Up trigger (any a[href$="/edit"])
        const editLink = event.target.closest('a[href$="/edit"]');
        if (editLink && editModal && editBody && !event.ctrlKey && !event.metaKey && !event.shiftKey) {
            event.preventDefault();
            const url = editLink.getAttribute('href');
            const isPasswordAction = editLink.getAttribute('data-focus-password') === 'true';
            if (editTitle) editTitle.textContent = isPasswordAction ? 'Memuat Formulir Ubah Sandi...' : 'Memuat Formulir Edit...';
            editBody.innerHTML = '<div class="py-4 text-center text-secondary">Sedang memuat formulir...</div>';
            editModal.classList.add('is-open');
            editModal.setAttribute('aria-hidden', 'false');

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(res => {
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.text();
                })
                .then(html => {
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const h1 = doc.querySelector('main h1');
                    const cardBody = doc.querySelector('main .card .card-body') || doc.querySelector('main form');
                    if (!cardBody) {
                        window.location.href = url;
                        return;
                    }
                    if (editTitle) editTitle.textContent = isPasswordAction ? 'Perbarui Kata Sandi Pengguna' : (h1 ? h1.textContent.trim() : 'Ubah Data');
                    editBody.innerHTML = cardBody.classList.contains('card-body') ? cardBody.innerHTML : cardBody.outerHTML;
                    editBody.querySelectorAll('a.btn-light').forEach(cancelLink => {
                        const closeBtn = document.createElement('button');
                        closeBtn.type = 'button';
                        closeBtn.className = cancelLink.className;
                        closeBtn.setAttribute('data-popup-close', '');
                        closeBtn.textContent = cancelLink.textContent.trim() || 'Batal';
                        cancelLink.replaceWith(closeBtn);
                    });
                    if (isPasswordAction) {
                        const passInput = editBody.querySelector('#field_password');
                        if (passInput) {
                            passInput.focus();
                            passInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            return;
                        }
                    }
                    const firstInput = editBody.querySelector('input:not([type="hidden"]), select, textarea');
                    if (firstInput) firstInput.focus();
                })
                .catch(() => {
                    window.location.href = url;
                });
        }
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') closePopups();
    });

    if (deleteConfirmBtn) {
        deleteConfirmBtn.addEventListener('click', () => {
            if (!pendingDeleteForm) return;
            const form = pendingDeleteForm;
            form.dataset.confirmed = 'true';
            closePopups();
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
    }

    document.addEventListener('submit', event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        const isDelete = form.hasAttribute('data-confirm') || !!form.querySelector('input[name="_method"][value="DELETE"]');
        if (isDelete && form.dataset.confirmed !== 'true') {
            if (deleteModal && deleteMessage) {
                event.preventDefault();
                pendingDeleteForm = form;
                deleteMessage.textContent = form.dataset.confirm || 'Apakah Anda yakin ingin menghapus data ini?';
                deleteModal.classList.add('is-open');
                deleteModal.setAttribute('aria-hidden', 'false');
                if (deleteConfirmBtn) deleteConfirmBtn.focus();
            } else if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
            return;
        }

        if (form.method.toLowerCase() === 'post') {
            if (event.defaultPrevented || !form.checkValidity() || form.dataset.submitting === 'true') {
                if (form.dataset.submitting === 'true') event.preventDefault();
                return;
            }
            form.dataset.submitting = 'true';
            const submitter = event.submitter;
            if (submitter) {
                submitter.setAttribute('aria-disabled', 'true');
                submitter.classList.add('is-submitting');
            }
        }
    });

    const quizForm = document.querySelector('[data-quiz-deadline]');
    if (quizForm) {
        const expires = Date.parse(quizForm.dataset.quizDeadline);
        const clock = document.getElementById('quizClock');
        if (Number.isFinite(expires) && clock) {
            let timer;
            const tick = () => {
                const seconds = Math.max(0, Math.floor((expires - Date.now()) / 1000));
                clock.textContent = Math.floor(seconds / 60) + ':' + String(seconds % 60).padStart(2, '0');
                if (seconds <= 1) {
                    clearInterval(timer);
                    if (quizForm.dataset.submitting !== 'true') quizForm.requestSubmit();
                }
            };
            timer = setInterval(tick, 1000);
            tick();
        }
    }

    // Bulk attendance helpers ("Tandai Semua Hadir" & late-minute auto-adjustment)
    document.querySelectorAll('[data-bulk-attendance]').forEach(form => {
        const threshold = Number(form.dataset.lateThreshold) || 0;
        const adjustMinutes = select => {
            const minutes = select.closest('.bulk-attendance-row')?.querySelector('[data-attendance-minutes]');
            if (!minutes) return;
            if (select.value === 'present') minutes.value = '0';
            else if (select.value === 'late' && (minutes.value === '' || Number(minutes.value) < threshold)) minutes.value = String(threshold);
            else if (['absent', 'excused'].includes(select.value)) minutes.value = '';
        };
        form.querySelector('[data-mark-all-present]')?.addEventListener('click', () => {
            form.querySelectorAll('[data-attendance-status]').forEach(select => {
                if (!select.disabled && !select.closest('fieldset')?.disabled) {
                    select.value = 'present';
                    adjustMinutes(select);
                }
            });
        });
        form.querySelectorAll('[data-attendance-status]').forEach(select => {
            select.addEventListener('change', () => adjustMinutes(select));
        });
    });
})();
