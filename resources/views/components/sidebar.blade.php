
@props(['section' => 'person'])

@php
    $menus = [
        
'person' => [
    [
        'label' => 'Person Management',
        'type' => 'dropdown',
        'icon' => 'users',
        'children' => [
            [
            'label' => 'Person',
            'route' => 'hr.person.index',
            'icon' => 'users',
        ],
            
            [
                'label' => 'Resigned',
                'route' => 'hr.person.resigned',
                'icon' => 'history',
            ],
            [
                'label' => 'To Be Reviewed',
                'route' => 'hr.person.to-be-reviewed',
                'icon' => 'clipboard',
            ],
            
        ],
    ],
    [
        'label' => 'Position Management',
        'type' => 'page',
        'route' => 'hr.person.position-management',
        'icon' => 'briefcase',
    ],
    [
        'label' => 'Additional Information',
        'type' => 'page',
        'route' => 'hr.person.additional-information',
        'icon' => 'sliders',
    ],
    [
        'label' => 'Basic Configuration',
        'type' => 'dropdown',
        'icon' => 'settings',
        'children' => [
            [
                'label' => 'Person Settings',
                'route' => null,
                'icon' => 'settings',
            ],
            [
                'label' => 'Credential Settings',
                'route' => null,
                'icon' => 'key',
            ],
            [
                'label' => 'Self-Registration Settings',
                'route' => null,
                'icon' => 'clipboard',
            ],
        ],
    ],
],


        'device' => [
            [
                'label' => 'Device and Server',
                'type' => 'heading',
            ],
            [
                'label' => 'Access Control Device',
                'type' => 'page',
                'route' => 'hr.device.index',
                'icon' => 'device',
            ],
        ],

        'attendance' => [
            [
                'label' => 'Attendance Management',
                'type' => 'heading',
            ],
            [
                'label' => 'Attendance Group',
                'type' => 'page',
                'route' => 'hr.shifts-attendance.index',
                'icon' => 'users',
            ],
            [
                'label' => 'Leave & Approvals',
                'type' => 'page',
                'route' => 'hr.leaves.index',
                'icon' => 'calendar',
            ],
        ],

        'payroll' => [
            [
                'label' => 'Payroll Management',
                'type' => 'heading',
            ],
            [
                'label' => 'Payroll & Payslips',
                'type' => 'page',
                'route' => 'hr.payroll.index',
                'icon' => 'wallet',
            ],
            [
                'label' => 'HR Reports',
                'type' => 'page',
                'route' => 'hr.reports.index',
                'icon' => 'chart',
            ],
        ],
    ];

    $heading = [
        'person' => 'Person',
        'device' => 'Device',
        'attendance' => 'Attendance',
        'payroll' => 'Payroll',
    ][$section] ?? 'Navigation';

    $icons = [
        'users' => '<circle cx="9" cy="8" r="3"/><path d="M3 20v-2a6 6 0 0 1 12 0v2"/><path d="M16 5a3 3 0 0 1 0 6M17 14a5 5 0 0 1 4 5"/>',
        'history' => '<path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 3v5h5M12 7v5l4 2"/>',
        'briefcase' => '<rect x="3" y="7" width="18" height="14" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 13h18"/>',
        'sliders' => '<path d="M4 4v16M12 4v16M20 4v16"/><circle cx="4" cy="9" r="2"/><circle cx="12" cy="15" r="2"/><circle cx="20" cy="8" r="2"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9 7 7M17 17l2.1 2.1M19.1 4.9 17 7M7 17l-2.1 2.1"/>',
        'clipboard' => '<rect x="5" y="5" width="14" height="17" rx="2"/><path d="M9 5V3h6v2M9 12h6M9 16h4"/>',
        'key' => '<circle cx="8" cy="15" r="5"/><path d="m12 11 9-9M17 6l3 3M14 9l3 3"/>',
        'device' => '<rect x="5" y="2" width="14" height="20" rx="2"/><path d="M9 6h6M10 18h4"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 10h18"/>',
        'wallet' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 9h18M16 15h2"/>',
        'chart' => '<path d="M4 20V10M10 20V4M16 20v-8M22 20V7"/>',
    ];

    $activeRoute = \Illuminate\Support\Facades\Route::currentRouteName();

    $menuItems = $menus[$section] ?? [];

    $isRouteActive = function ($routeName) use ($activeRoute) {
        return $routeName !== null && $routeName === $activeRoute;
    };

    $isGroupActive = function ($children) use ($isRouteActive) {
        foreach ($children as $child) {
            if ($isRouteActive($child['route'] ?? null)) {
                return true;
            }
        }
        return false;
    };
@endphp

<style>
    .erp-side {
        --side-bg: #0b1018;
        --side-heading: #101722;
        --side-hover: #19212d;
        --side-border: #202a39;
        --side-red: #f10f32;

        width: 275px;
        min-width: 275px;
        flex: none;
        height: calc(100vh - var(--erp-header-height));
        height: calc(100dvh - var(--erp-header-height));
        min-height: 0;
        align-self: flex-start;

        background: var(--side-bg);
        color: #d7dce5;
        border-right: 1px solid var(--side-border);

        display: flex;
        flex-direction: column;
        position: sticky;
        top: var(--erp-header-height);
        transition: width .25s ease, min-width .25s ease;
    }

    .erp-side-heading {
        height: 64px;
        min-height: 64px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 17px;
        background: var(--side-heading);
        color: #9ca6b6;
        border-bottom: 1px solid #263040;
    }

    .erp-side-heading-title {
        font-size: 17px;
        font-weight: 700;
        white-space: nowrap;
    }

    .erp-side-toggle {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 0;
        border-radius: 6px;
        background: transparent;
        color: #aeb7c5;
        cursor: pointer;
    }

    .erp-side-toggle:hover {
        background: #263142;
        color: white;
    }

    .erp-side-toggle svg {
        width: 20px;
        height: 20px;
        fill: none;
        stroke: currentColor;
        stroke-width: 1.8;
        stroke-linecap: round;
    }

    .erp-side-heading-actions { display:flex; align-items:center; gap:4px; margin-left:auto; }
    .erp-side-account { position:relative; }
    .erp-side-account summary { list-style:none; }
    .erp-side-account summary::-webkit-details-marker { display:none; }
    .erp-side-account-menu { position:absolute; right:0; top:calc(100% + 8px); width:180px; padding:6px; border:1px solid #263040; border-radius:8px; background:#101722; box-shadow:0 8px 20px rgba(0,0,0,.25); z-index:60; }
    .erp-side-account-menu a, .erp-side-account-menu button { display:block; width:100%; padding:10px 12px; border:0; border-radius:5px; background:transparent; color:#d7dce5; font:inherit; font-size:13px; text-align:left; text-decoration:none; cursor:pointer; }
    .erp-side-account-menu a:hover, .erp-side-account-menu button:hover { background:#263142; color:#fff; }
    .erp-side-account-menu form { margin:0; }
    .erp-side.is-collapsed .erp-side-account { display:none; }

    .erp-side-menu {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 12px 0;
    }

    .erp-side-group {
        padding: 15px 18px;
        margin: 4px 0;
        background: #0d131d;
        color: #f0f2f6;
        border-bottom: 1px solid var(--side-border);
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .erp-side-link,
    .erp-side-dropdown {
        width: 100%;
        min-height: 47px;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 17px;
        color: #bfc7d4;
        background: transparent;
        border: none;
        border-left: 4px solid transparent;
        text-decoration: none;
        font-family: inherit;
        font-size: 13px;
        font-weight: 500;
        text-align: left;
        cursor: pointer;
        transition: background .2s, color .2s;
    }

    .erp-side-link:hover,
    .erp-side-dropdown:hover {
        background: var(--side-hover);
        color: #fff;
    }

    .erp-side-link.selected {
        background: linear-gradient(
            90deg,
            rgba(241,15,50,.32) 0%,
            rgba(91,17,30,.55) 55%,
            rgba(11,16,24,.1) 100%
        );
        border-left-color: var(--side-red);
        color: #fff;
        font-weight: 600;
    }

    .erp-side-dropdown {
        background: #151c27;
        color: #f0f2f6;
        font-weight: 600;
        justify-content: space-between;
    }

    .erp-side-dropdown-left {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .erp-side-dropdown-chevron {
        width: 16px;
        height: 16px;
        flex-shrink: 0;
        transition: transform .2s ease;
    }

    .erp-side-dropdown[aria-expanded="true"]
    .erp-side-dropdown-chevron {
        transform: rotate(180deg);
    }

    .erp-side-children {
        background: #0b1018;
    }

    .erp-side-children[hidden] {
        display: none !important;
    }

    .erp-side-children .erp-side-link {
        padding-left: 39px;
    }

    .erp-side-icon {
        width: 19px;
        height: 19px;
        display: inline-flex;
        flex-shrink: 0;
        color: #9ca6b6;
    }

    .erp-side-icon svg {
        width: 19px;
        height: 19px;
        fill: none;
        stroke: currentColor;
        stroke-width: 1.7;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .erp-side-link.selected .erp-side-icon,
    .erp-side-link:hover .erp-side-icon {
        color: white;
    }

    .erp-side-label {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .erp-side-footer {
        padding: 14px 17px;
        background: #0d131d;
        border-top: 1px solid var(--side-border);
    }

    .erp-side-footer-title {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 1px;
        color: #9ca6b6;
    }

    .erp-side-footer-subtitle {
        margin-top: 5px;
        font-size: 11px;
        color: #667487;
    }

    .erp-side.is-collapsed {
        width: 72px;
        min-width: 72px;
    }

    .erp-side.is-collapsed .erp-side-heading-title,
    .erp-side.is-collapsed .erp-side-label,
    .erp-side.is-collapsed .erp-side-footer,
    .erp-side.is-collapsed .erp-side-group,
    .erp-side.is-collapsed .erp-side-dropdown-chevron {
        display: none;
    }

    .erp-side.is-collapsed .erp-side-heading,
    .erp-side.is-collapsed .erp-side-link,
    .erp-side.is-collapsed .erp-side-dropdown {
        justify-content: center;
        padding-left: 0;
        padding-right: 0;
    }

    .erp-side.is-collapsed .erp-side-children {
        display: none !important;
    }

    .erp-side-menu::-webkit-scrollbar {
        width: 5px;
    }

    .erp-side-menu::-webkit-scrollbar-thumb {
        background: #364052;
        border-radius: 8px;
    }

    @media (max-width: 900px) {
        .erp-side {
            width: 220px;
            min-width: 220px;
        }
    }

    @media (max-width: 600px) {
        .erp-side {
            width: 100%;
            min-width: 100%;
            height: auto;
            min-height: auto;
            position: sticky;
            z-index: 40;
        }

        .erp-side-menu {
            max-height: 350px;
        }

        .erp-side.is-collapsed {
            width: 100%;
            min-width: 100%;
        }

        .erp-side.is-collapsed .erp-side-menu {
            display: none;
        }

        .erp-side.is-collapsed .erp-side-heading-title {
            display: block;
        }

        .erp-side.is-collapsed .erp-side-heading {
            justify-content: space-between;
            padding: 0 17px;
        }
        /* Selected item - red gradient */
.erp-side-link.selected {
    background: linear-gradient(
        90deg,
        rgba(241, 15, 50, 0.35) 0%,
        rgba(91, 17, 30, 0.55) 55%,
        rgba(11, 16, 24, 0.1) 100%
    ) !important;

    border-left: 4px solid #f10f32 !important;
    color: #ffffff !important;
    font-weight: 600;
}

.erp-side-link.selected .erp-side-icon {
    color: #ffffff;
}

/* Dropdown headings should not get the gradient */
.erp-side-dropdown {
    border-left: 4px solid transparent;
}

/* Unconnected pages should still be clickable */
.erp-side-link[data-menu-item] {
    cursor: pointer;
}
    }
</style>

<aside
    class="erp-side"
    id="erpSidebar"
    aria-label="{{ $heading }} sidebar"
>
    <div class="erp-side-heading">
        <span class="erp-side-heading-title">
            {{ $heading }}
        </span>

        <div class="erp-side-heading-actions">
        @auth
            <details class="erp-side-account">
                <summary class="erp-side-toggle" aria-label="Account options" title="Account options">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M4 21v-2a8 8 0 0 1 16 0v2"/>
                    </svg>
                </summary>
                <div class="erp-side-account-menu">
                    <a href="{{ route('profile.edit') }}#update_password_current_password">Change Password</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <input type="hidden" name="_redirect_after_logout" value="1">
                        <button type="submit">Logout</button>
                    </form>
                </div>
            </details>
        @endauth
        <button
            type="button"
            class="erp-side-toggle"
            id="erpSidebarToggle"
            aria-label="Toggle sidebar"
            aria-controls="erpSidebar"
            aria-expanded="true"
        >
            <svg viewBox="0 0 24 24">
                <path d="M4 7h16M4 12h16M4 17h16"/>
            </svg>
        </button>
        </div>
    </div>

    <nav class="erp-side-menu">
        @foreach($menuItems as $index => $item)
            @php
                $type = $item['type'] ?? 'page';
                $routeName = $item['route'] ?? null;
                $hasRoute = $routeName &&
                    \Illuminate\Support\Facades\Route::has($routeName);
                $iconName = $item['icon'] ?? 'users';
            @endphp

            @if($type === 'heading')
                <div class="erp-side-group">
                    {{ $item['label'] }}
                </div>

            @elseif($type === 'dropdown')
                @php
                    $children = $item['children'] ?? [];
                    $groupActive = $isGroupActive($children);
                    $groupId = 'erp-group-' . $section . '-' . $index;
                @endphp

                <button
                    type="button"
                    class="erp-side-dropdown"
                    aria-expanded="{{ $groupActive ? 'true' : 'false' }}"
                    aria-controls="{{ $groupId }}"
                    data-erp-dropdown
                >
                    <span class="erp-side-dropdown-left">
                        <span class="erp-side-icon">
                            <svg viewBox="0 0 24 24">
                                {!! $icons[$iconName] ?? $icons['users'] !!}
                            </svg>
                        </span>

                        <span class="erp-side-label">
                            {{ $item['label'] }}
                        </span>
                    </span>

                    <svg
                        class="erp-side-dropdown-chevron"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="m6 9 6 6 6-6"/>
                    </svg>
                </button>

                <div
                    id="{{ $groupId }}"
                    class="erp-side-children"
                    @if(!$groupActive) hidden @endif
                >
                    @foreach($children as $child)
                        @php
                            $childRoute = $child['route'] ?? null;
                            $childHasRoute = $childRoute &&
                                \Illuminate\Support\Facades\Route::has($childRoute);
                            $childActive = $isRouteActive($childRoute);
                            $childIcon = $child['icon'] ?? 'users';
                        @endphp

                        @if($childHasRoute)
                            <a
                                href="{{ route($childRoute) }}"
                                class="erp-side-link {{ $childActive ? 'selected' : '' }}"
                                @if($childActive) aria-current="page" @endif
                            >
                                <span class="erp-side-icon">
                                    <svg viewBox="0 0 24 24">
                                        {!! $icons[$childIcon] ?? $icons['users'] !!}
                                    </svg>
                                </span>
                                <span class="erp-side-label">
                                    {{ $child['label'] }}
                                </span>
                            </a>
                        @else
                            <span
                                class="erp-side-link"
                                title="{{ $child['label'] }} - Page not connected yet"
                            >
                                <span class="erp-side-icon">
                                    <svg viewBox="0 0 24 24">
                                        {!! $icons[$childIcon] ?? $icons['users'] !!}
                                    </svg>
                                </span>
                                <span class="erp-side-label">
                                    {{ $child['label'] }}
                                </span>
                            </span>
                        @endif
                    @endforeach
                </div>

            @else
                @php
                    $isSelected = $isRouteActive($routeName);
                @endphp

                @if($hasRoute)
                    <a
                        href="{{ route($routeName) }}"
                        class="erp-side-link {{ $isSelected ? 'selected' : '' }}"
                        @if($isSelected) aria-current="page" @endif
                    >
                        <span class="erp-side-icon">
                            <svg viewBox="0 0 24 24">
                                {!! $icons[$iconName] ?? $icons['users'] !!}
                            </svg>
                        </span>
                        <span class="erp-side-label">
                            {{ $item['label'] }}
                        </span>
                    </a>
                @else
                    <span
                        class="erp-side-link"
                        title="{{ $item['label'] }} - Page not connected yet"
                    >
                        <span class="erp-side-icon">
                            <svg viewBox="0 0 24 24">
                                {!! $icons[$iconName] ?? $icons['users'] !!}
                            </svg>
                        </span>
                        <span class="erp-side-label">
                            {{ $item['label'] }}
                        </span>
                    </span>
                @endif
            @endif
        @endforeach
    </nav>

    <div class="erp-side-footer">
        <div class="erp-side-footer-title">
            PARTNER IT ERP
        </div>
        <div class="erp-side-footer-subtitle">
            HR & Payroll Management
        </div>
    </div>
</aside>

<script>
    (function () {
        const sidebar = document.getElementById('erpSidebar');
        const sidebarToggle = document.getElementById('erpSidebarToggle');

        if (!sidebar) return;

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function () {
                sidebar.classList.toggle('is-collapsed');

                sidebarToggle.setAttribute(
                    'aria-expanded',
                    String(!sidebar.classList.contains('is-collapsed'))
                );
            });
        }

        const dropdowns = sidebar.querySelectorAll(
            '[data-erp-dropdown]'
        );

        dropdowns.forEach(function (button) {
            const targetId = button.getAttribute('aria-controls');
            const target = document.getElementById(targetId);

            if (!target) return;

            button.addEventListener('click', function () {
                const isExpanded =
                    button.getAttribute('aria-expanded') === 'true';

                button.setAttribute(
                    'aria-expanded',
                    String(!isExpanded)
                );

                target.hidden = isExpanded;
            });
        });
    })();
</script>
