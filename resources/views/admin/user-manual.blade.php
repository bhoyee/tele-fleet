<x-admin-layout>
    @push('styles')
        <style>
            .markdown-body {
                font-size: 0.95rem;
                line-height: 1.7;
                color: #1f2937;
            }

            .markdown-body h1,
            .markdown-body h2,
            .markdown-body h3,
            .markdown-body h4 {
                color: #056CA3;
                margin-top: 1.5rem;
                margin-bottom: 0.75rem;
                font-weight: 700;
            }

            .markdown-body [id] {
                scroll-margin-top: 120px;
            }

            .markdown-body p {
                margin-bottom: 1rem;
            }

            .markdown-body a {
                color: #056CA3;
                text-decoration: none;
            }

            .markdown-body a:hover {
                text-decoration: underline;
            }

            .markdown-body img {
                max-width: 100%;
                height: auto;
                max-height: 420px;
                object-fit: contain;
                border-radius: 12px;
                box-shadow: 0 8px 24px rgba(5, 108, 163, 0.12);
                margin: 1rem 0;
                cursor: zoom-in;
            }

            .manual-lightbox {
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, 0.8);
                display: none;
                align-items: center;
                justify-content: center;
                z-index: 2000;
                padding: 2rem;
            }

            .manual-lightbox.active {
                display: flex;
            }

            .manual-lightbox img {
                max-width: 95vw;
                max-height: 90vh;
                border-radius: 16px;
                box-shadow: 0 16px 40px rgba(0, 0, 0, 0.35);
                cursor: zoom-out;
            }

            .manual-lightbox-close {
                position: absolute;
                top: 20px;
                right: 24px;
                background: rgba(255, 255, 255, 0.15);
                border: none;
                color: #fff;
                font-size: 1.5rem;
                width: 40px;
                height: 40px;
                border-radius: 999px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .markdown-body pre {
                background: #0f172a;
                color: #e2e8f0;
                padding: 1rem;
                border-radius: 12px;
                overflow-x: auto;
            }

            .markdown-body code {
                background: rgba(5, 108, 163, 0.1);
                padding: 0.2rem 0.35rem;
                border-radius: 6px;
                font-size: 0.9em;
            }

            .markdown-body pre code {
                background: transparent;
                padding: 0;
            }

            .markdown-body table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 1.5rem;
            }

            .markdown-body table th,
            .markdown-body table td {
                border: 1px solid rgba(5, 108, 163, 0.15);
                padding: 0.75rem;
                text-align: left;
            }

            .markdown-body table th {
                background: rgba(5, 108, 163, 0.08);
            }

            @media (max-width: 768px) {
                .markdown-body {
                    font-size: 0.9rem;
                }

                .markdown-body table {
                    display: block;
                    overflow-x: auto;
                }
            }
        </style>
    @endpush

    <div class="page-header">
        <h1>User Manual</h1>
        <p class="text-muted mb-0">Reference guide for administrators and staff.</p>
    </div>

    <div class="card">
        <div class="card-body markdown-body">
            {!! $content !!}
        </div>
    </div>

    <div class="manual-lightbox" id="manualLightbox" aria-hidden="true">
        <button class="manual-lightbox-close" type="button" id="manualLightboxClose" aria-label="Close">&times;</button>
        <img src="" alt="Manual preview" id="manualLightboxImage">
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const lightbox = document.getElementById('manualLightbox');
                const lightboxImage = document.getElementById('manualLightboxImage');
                const closeButton = document.getElementById('manualLightboxClose');

                if (!lightbox || !lightboxImage) {
                    return;
                }

                document.querySelectorAll('.markdown-body img').forEach((img) => {
                    img.addEventListener('click', () => {
                        lightboxImage.src = img.src;
                        lightbox.classList.add('active');
                        lightbox.setAttribute('aria-hidden', 'false');
                    });
                });

                const closeLightbox = () => {
                    lightbox.classList.remove('active');
                    lightbox.setAttribute('aria-hidden', 'true');
                    lightboxImage.src = '';
                };

                lightbox.addEventListener('click', (event) => {
                    if (event.target === lightbox) {
                        closeLightbox();
                    }
                });

                if (closeButton) {
                    closeButton.addEventListener('click', closeLightbox);
                }

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && lightbox.classList.contains('active')) {
                        closeLightbox();
                    }
                });
            });
        </script>
    @endpush
</x-admin-layout>
