<!doctype html>
<html lang="en" data-theme="{{ $config->get('ui.theme', 'light') }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="color-scheme" content="{{ $config->get('ui.theme', 'light') }}">
    <title>{{ $config->get('ui.title', config('app.name') . ' - API Docs') }}</title>

    <script src="https://unpkg.com/@stoplight/elements@8.4.2/web-components.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/@stoplight/elements@8.4.2/styles.min.css">

    <script>
        const originalFetch = window.fetch;

        // intercept TryIt requests and add the XSRF-TOKEN header,
        // which is necessary for Sanctum cookie-based authentication to work correctly
        window.fetch = (url, options) => {
            const CSRF_TOKEN_COOKIE_KEY = "XSRF-TOKEN";
            const CSRF_TOKEN_HEADER_KEY = "X-XSRF-TOKEN";
            const getCookieValue = (key) => {
                const cookie = document.cookie.split(';').find((cookie) => cookie.trim().startsWith(key));
                return cookie?.split("=")[1];
            };

            const updateFetchHeaders = (
                headers,
                headerKey,
                headerValue,
            ) => {
                if (headers instanceof Headers) {
                    headers.set(headerKey, headerValue);
                } else if (Array.isArray(headers)) {
                    headers.push([headerKey, headerValue]);
                } else if (headers) {
                    headers[headerKey] = headerValue;
                }
            };
            const csrfToken = getCookieValue(CSRF_TOKEN_COOKIE_KEY);
            if (csrfToken) {
                const { headers = new Headers() } = options || {};
                updateFetchHeaders(headers, CSRF_TOKEN_HEADER_KEY, decodeURIComponent(csrfToken));
                return originalFetch(url, {
                    ...options,
                    headers,
                });
            }

            return originalFetch(url, options);
        };
    </script>

    <style>
        html,
        body {
            margin: 0;
            height: 100%;
        }

        body {
            background-color: var(--color-canvas);
        }

        /* issues about the dark theme of stoplight/mosaic-code-viewer using web component:
         * https://github.com/stoplightio/elements/issues/2188#issuecomment-1485461965
         */
        [data-theme="dark"] .token.property {
            color: rgb(128, 203, 196) !important;
        }

        [data-theme="dark"] .token.operator {
            color: rgb(255, 123, 114) !important;
        }

        [data-theme="dark"] .token.number {
            color: rgb(247, 140, 108) !important;
        }

        [data-theme="dark"] .token.string {
            color: rgb(165, 214, 255) !important;
        }

        [data-theme="dark"] .token.boolean {
            color: rgb(121, 192, 255) !important;
        }

        [data-theme="dark"] .token.punctuation {
            color: #dbdbdb !important;
        }
    </style>
</head>

<body style="height: 100vh; overflow-y: hidden">
    <elements-api id="docs" tryItCredentialsPolicy="{{ $config->get('ui.try_it_credentials_policy', 'include') }}"
        router="hash" @if($config->get('ui.hide_try_it')) hideTryIt="true" @endif @if($config->get('ui.hide_schemas'))
        hideSchemas="true" @endif @if($config->get('ui.logo')) logo="{{ $config->get('ui.logo') }}" @endif
        @if($config->get('ui.layout')) layout="{{ $config->get('ui.layout') }}" @endif />
    <script>
        (async () => {
            const docs = document.getElementById('docs');
            docs.apiDescriptionDocument = @json($spec);
        })();
    </script>

    <script>
        // Auto-fill bearer token after successful login
        (function () {
            const originalFetch = window.fetch;

            window.fetch = async function (...args) {
                const response = await originalFetch.apply(this, args);

                // Clone response to read it without consuming the original
                const clonedResponse = response.clone();

                try {
                    // Check if this is a login request
                    const url = args[0];
                    if (typeof url === 'string' && url.includes('/api/v1/auth/login')) {
                        const data = await clonedResponse.json();

                        // Check if login was successful and has access_token
                        if (data.success && data.data && data.data.access_token) {
                            const token = data.data.access_token;

                            // Store token in localStorage
                            localStorage.setItem('api_bearer_token', token);

                            // Show enhanced notification with instructions
                            setTimeout(() => {
                                const notification = document.createElement('div');
                                notification.style.cssText = `
                                position: fixed;
                                top: 20px;
                                right: 20px;
                                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                color: white;
                                padding: 20px 24px;
                                border-radius: 12px;
                                box-shadow: 0 10px 25px rgba(0,0,0,0.2);
                                z-index: 10000;
                                font-family: system-ui, -apple-system, sans-serif;
                                max-width: 400px;
                                animation: slideIn 0.3s ease-out;
                            `;

                                notification.innerHTML = `
                                <div style="display: flex; align-items: start; gap: 12px;">
                                    <div style="font-size: 24px;">🔑</div>
                                    <div>
                                        <div style="font-weight: 600; font-size: 16px; margin-bottom: 8px;">
                                            Token Berhasil Disimpan!
                                        </div>
                                        <div style="font-size: 13px; line-height: 1.5; opacity: 0.95; margin-bottom: 12px;">
                                            Untuk menggunakan token di protected endpoints:
                                        </div>
                                        <ol style="font-size: 13px; line-height: 1.6; margin: 0; padding-left: 20px; opacity: 0.95;">
                                            <li>Klik <strong>"Security: Bearer Auth"</strong> di endpoint yang ingin di-test</li>
                                            <li>Paste token di field <strong>"Token"</strong></li>
                                            <li>Klik <strong>"Send API Request"</strong></li>
                                        </ol>
                                        <div style="margin-top: 12px; padding: 8px 12px; background: rgba(255,255,255,0.2); border-radius: 6px; font-family: monospace; font-size: 11px; word-break: break-all; cursor: pointer;" id="tokenCopy">
                                            ${token.substring(0, 50)}...
                                        </div>
                                        <div style="font-size: 11px; margin-top: 6px; opacity: 0.8; text-align: center;">
                                            👆 Klik untuk copy token
                                        </div>
                                    </div>
                                </div>
                            `;

                                // Add animation keyframes
                                const style = document.createElement('style');
                                style.textContent = `
                                @keyframes slideIn {
                                    from {
                                        transform: translateX(400px);
                                        opacity: 0;
                                    }
                                    to {
                                        transform: translateX(0);
                                        opacity: 1;
                                    }
                                }
                            `;
                                document.head.appendChild(style);

                                document.body.appendChild(notification);

                                // Copy token to clipboard when clicked
                                const tokenElement = notification.querySelector('#tokenCopy');
                                tokenElement.addEventListener('click', () => {
                                    navigator.clipboard.writeText(token).then(() => {
                                        tokenElement.style.background = 'rgba(16, 185, 129, 0.3)';
                                        tokenElement.textContent = '✅ Token copied to clipboard!';
                                        setTimeout(() => {
                                            tokenElement.style.background = 'rgba(255,255,255,0.2)';
                                            tokenElement.innerHTML = `${token.substring(0, 50)}...`;
                                        }, 2000);
                                    });
                                });

                                // Auto-hide after 10 seconds
                                setTimeout(() => {
                                    notification.style.transition = 'all 0.3s ease-out';
                                    notification.style.transform = 'translateX(400px)';
                                    notification.style.opacity = '0';
                                    setTimeout(() => notification.remove(), 300);
                                }, 10000);

                                // Close on click
                                notification.addEventListener('click', (e) => {
                                    if (e.target.id !== 'tokenCopy') {
                                        notification.style.transition = 'all 0.3s ease-out';
                                        notification.style.transform = 'translateX(400px)';
                                        notification.style.opacity = '0';
                                        setTimeout(() => notification.remove(), 300);
                                    }
                                });
                            }, 500);

                            console.log('✅ Token saved to localStorage!');
                            console.log('📋 Token:', token);
                            console.log('💡 To use: Click "Security: Bearer Auth" → Paste token → Send request');
                        }
                    }

                    // Check if this is a refresh token request
                    if (typeof url === 'string' && url.includes('/api/v1/auth/refresh')) {
                        const data = await clonedResponse.json();

                        // Check if refresh was successful and has access_token
                        if (data.success && data.data && data.data.access_token) {
                            const token = data.data.access_token;

                            // Store new token in localStorage
                            localStorage.setItem('api_bearer_token', token);

                            // Show notification for refresh token
                            setTimeout(() => {
                                const notification = document.createElement('div');
                                notification.style.cssText = `
                                position: fixed;
                                top: 20px;
                                right: 20px;
                                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                                color: white;
                                padding: 20px 24px;
                                border-radius: 12px;
                                box-shadow: 0 10px 25px rgba(0,0,0,0.2);
                                z-index: 10000;
                                font-family: system-ui, -apple-system, sans-serif;
                                max-width: 400px;
                                animation: slideIn 0.3s ease-out;
                            `;

                                notification.innerHTML = `
                                <div style="display: flex; align-items: start; gap: 12px;">
                                    <div style="font-size: 24px;">🔄</div>
                                    <div>
                                        <div style="font-weight: 600; font-size: 16px; margin-bottom: 8px;">
                                            Token Baru Berhasil Dibuat!
                                        </div>
                                        <div style="font-size: 13px; line-height: 1.5; opacity: 0.95; margin-bottom: 12px;">
                                            Gunakan token baru ini untuk melanjutkan akses:
                                        </div>
                                        <ol style="font-size: 13px; line-height: 1.6; margin: 0; padding-left: 20px; opacity: 0.95;">
                                            <li>Copy token baru di bawah</li>
                                            <li>Update token di <strong>"Security: Bearer Auth"</strong></li>
                                            <li>Token lama sudah tidak valid</li>
                                        </ol>
                                        <div style="margin-top: 12px; padding: 8px 12px; background: rgba(255,255,255,0.2); border-radius: 6px; font-family: monospace; font-size: 11px; word-break: break-all; cursor: pointer;" id="refreshTokenCopy">
                                            ${token.substring(0, 50)}...
                                        </div>
                                        <div style="font-size: 11px; margin-top: 6px; opacity: 0.8; text-align: center;">
                                            👆 Klik untuk copy token
                                        </div>
                                    </div>
                                </div>
                            `;

                                document.body.appendChild(notification);

                                // Copy token to clipboard when clicked
                                const tokenElement = notification.querySelector('#refreshTokenCopy');
                                tokenElement.addEventListener('click', () => {
                                    navigator.clipboard.writeText(token).then(() => {
                                        tokenElement.style.background = 'rgba(16, 185, 129, 0.5)';
                                        tokenElement.textContent = '✅ Token copied to clipboard!';
                                        setTimeout(() => {
                                            tokenElement.style.background = 'rgba(255,255,255,0.2)';
                                            tokenElement.innerHTML = `${token.substring(0, 50)}...`;
                                        }, 2000);
                                    });
                                });

                                // Auto-hide after 10 seconds
                                setTimeout(() => {
                                    notification.style.transition = 'all 0.3s ease-out';
                                    notification.style.transform = 'translateX(400px)';
                                    notification.style.opacity = '0';
                                    setTimeout(() => notification.remove(), 300);
                                }, 10000);

                                // Close on click
                                notification.addEventListener('click', (e) => {
                                    if (e.target.id !== 'refreshTokenCopy') {
                                        notification.style.transition = 'all 0.3s ease-out';
                                        notification.style.transform = 'translateX(400px)';
                                        notification.style.opacity = '0';
                                        setTimeout(() => notification.remove(), 300);
                                    }
                                });
                            }, 500);

                            console.log('🔄 Token refreshed and saved to localStorage!');
                            console.log('📋 New Token:', token);
                        }
                    }
                } catch (e) {
                    // Ignore JSON parse errors for non-JSON responses
                }

                return response;
            };

            // On page load, show saved token info
            window.addEventListener('load', () => {
                const savedToken = localStorage.getItem('api_bearer_token');
                if (savedToken) {
                    console.log('💡 Token tersimpan ditemukan!');
                    console.log('📋 Token:', savedToken);
                    console.log('🔧 Untuk menggunakan: Klik "Security: Bearer Auth" di endpoint yang ingin di-test');
                }
            });
        })();
    </script>

    @if($config->get('ui.theme', 'light') === 'system')
        <script>
            var mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

            function updateTheme(e) {
                if (e.matches) {
                    window.document.documentElement.setAttribute('data-theme', 'dark');
                    window.document.getElementsByName('color-scheme')[0].setAttribute('content', 'dark');
                } else {
                    window.document.documentElement.setAttribute('data-theme', 'light');
                    window.document.getElementsByName('color-scheme')[0].setAttribute('content', 'light');
                }
            }

            mediaQuery.addEventListener('change', updateTheme);
            updateTheme(mediaQuery);
        </script>
    @endif
</body>

</html>