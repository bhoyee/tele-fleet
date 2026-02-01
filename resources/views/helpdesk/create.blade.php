<x-admin-layout>
    <style>
        @media (max-width: 767px) {
            .helpdesk-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .helpdesk-actions {
                width: 100%;
            }

            .helpdesk-actions .btn {
                width: 100%;
            }

            .helpdesk-form .row > [class*='col-'] {
                flex: 0 0 100%;
                max-width: 100%;
            }

            .helpdesk-submit {
                width: 100%;
                justify-content: stretch;
            }

            .helpdesk-submit .btn {
                width: 100%;
            }
        }
    </style>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2 helpdesk-header">
        <div>
            <h1 class="h3 mb-1">New Support Ticket</h1>
            <p class="text-muted mb-0">Provide details so we can assist quickly.</p>
        </div>
        <div class="helpdesk-actions">
            <a class="btn btn-outline-secondary" href="{{ route('helpdesk.index') }}">Back to Tickets</a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" action="{{ route('helpdesk.store') }}" enctype="multipart/form-data" id="helpdeskCreateForm" class="helpdesk-form">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category" required>
                            <option value="">Select category</option>
                            <option value="administrative" @selected(old('category') === 'administrative')>Administrative</option>
                            <option value="technical" @selected(old('category') === 'technical')>Technical Support</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Priority</label>
                        <select class="form-select" name="priority" required>
                            <option value="">Select priority</option>
                            <option value="low" @selected(old('priority') === 'low')>Low</option>
                            <option value="medium" @selected(old('priority') === 'medium')>Medium</option>
                            <option value="high" @selected(old('priority') === 'high')>High</option>
                            <option value="critical" @selected(old('priority') === 'critical')>Critical</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Subject</label>
                        <input class="form-control" name="subject" type="text" maxlength="150" value="{{ old('subject') }}" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" id="ticketDescription" name="description" rows="6">{{ old('description') }}</textarea>
                        <div class="text-muted small mt-1">Description is required.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Attachments (images, PDF, DOC/DOCX)</label>
                        <input class="form-control" type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx">
                        <div class="text-muted small mt-1">Max size per file: 10MB.</div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4 helpdesk-submit">
                    <button class="btn btn-primary" type="submit" id="helpdeskSubmitBtn">Submit Ticket</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
        <script>
            const helpdeskForm = document.getElementById('helpdeskCreateForm');
            const helpdeskSubmitBtn = document.getElementById('helpdeskSubmitBtn');

            if (window.tinymce) {
                tinymce.init({
                    selector: '#ticketDescription',
                    height: 260,
                    menubar: false,
                    plugins: 'lists link',
                    toolbar: 'undo redo | bold italic | bullist numlist | link',
                });
            }

            const applyLoadingState = (button) => {
                if (!button || button.classList.contains('btn-loading')) {
                    return;
                }
                const label = document.createElement('span');
                label.className = 'btn-label';
                label.textContent = button.textContent.trim();
                const spinner = document.createElement('span');
                spinner.className = 'spinner-border spinner-border-sm btn-spinner';
                spinner.setAttribute('role', 'status');
                spinner.setAttribute('aria-hidden', 'true');
                button.textContent = '';
                button.appendChild(label);
                button.appendChild(spinner);
                button.classList.add('btn-loading');
                button.setAttribute('disabled', 'disabled');
            };

            if (helpdeskSubmitBtn && helpdeskForm) {
                helpdeskForm.addEventListener('submit', (event) => {
                    if (window.tinymce) {
                        tinymce.triggerSave();
                    }
                    const descriptionValue = helpdeskForm.querySelector('[name=\"description\"]')?.value?.trim();
                    if (!descriptionValue) {
                        event.preventDefault();
                        alert('Please enter a description for the ticket.');
                        return;
                    }
                    if (!helpdeskForm.checkValidity()) {
                        event.preventDefault();
                        helpdeskForm.reportValidity();
                        return;
                    }
                    applyLoadingState(helpdeskSubmitBtn);
                });
            }
        </script>
    @endpush
</x-admin-layout>
