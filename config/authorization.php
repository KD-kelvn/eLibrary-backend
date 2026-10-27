<?php

/**
 * Route → permission map for the bare `permission` middleware (V1-004).
 *
 * Keys: "{METHOD} {path}" (path includes leading api/…).
 * Values: list of action codes (any-of). Empty list = auth-only (no action grant).
 *
 * Prefer matrix codes from design/docs/v1/03-roles-permissions.md; legacy
 * seeder codes remain as alternatives until menus are remapped.
 */
return [
    'route_permissions' => [
        // Session / self
        'GET api/auth/me' => [],
        'POST api/auth/logout' => [],
        'GET api/auth/sessions' => ['sessions.view_own', 'sessions.view'],
        'DELETE api/auth/sessions/others' => ['sessions.revoke_own', 'sessions.revoke'],
        'DELETE api/auth/sessions/{personalAccessToken}' => ['sessions.revoke_own', 'sessions.revoke'],

        // Admin sessions
        'GET api/auth/admin/sessions' => ['sessions.manage', 'sessions.view'],
        'GET api/auth/admin/sessions/summary' => ['sessions.manage', 'sessions.view'],
        'DELETE api/auth/admin/sessions/{personalAccessToken}' => ['sessions.manage', 'sessions.revoke'],
        'DELETE api/auth/admin/sessions/by-user/{user}' => ['sessions.manage', 'sessions.revoke'],
        'GET api/auth/admin-dashboard' => ['roles.manage', 'users.manage', 'users.create'],

        // Users
        'GET api/auth/users' => ['users.manage', 'users.create', 'users.update'],
        'POST api/auth/users' => ['users.manage', 'users.create'],
        'GET api/auth/users/{user}' => ['users.manage', 'users.update'],
        'PUT api/auth/users/{user}' => ['users.manage', 'users.update'],
        'PATCH api/auth/users/{user}' => ['users.manage', 'users.update'],
        'PATCH api/auth/users/{user}/blocked' => ['users.manage', 'users.block'],
        'PATCH api/auth/users/{user}/active' => ['users.manage', 'users.update', 'users.delete'],
        'POST api/auth/users/{user}/photo' => ['users.manage', 'users.update'],
        'GET api/auth/users/{user}/system-actions' => ['permissions.assign', 'actions.update'],
        'PUT api/auth/users/{user}/system-actions' => ['permissions.assign', 'actions.update'],

        // Roles
        'GET api/auth/roles' => ['roles.manage', 'roles.create', 'roles.update'],
        'POST api/auth/roles' => ['roles.manage', 'roles.create'],
        'GET api/auth/roles/{role}' => ['roles.manage', 'roles.update'],
        'PUT api/auth/roles/{role}' => ['roles.manage', 'roles.update'],
        'PATCH api/auth/roles/{role}' => ['roles.manage', 'roles.update'],
        'DELETE api/auth/roles/{role}' => ['roles.manage', 'roles.delete'],

        // Role assignments / revocations
        'GET api/auth/role-assignments' => ['roles.manage', 'role-assignments.create', 'role-assignments.update'],
        'POST api/auth/role-assignments' => ['roles.manage', 'role-assignments.create'],
        'GET api/auth/role-assignments/{role_assignment}' => ['roles.manage', 'role-assignments.update'],
        'PUT api/auth/role-assignments/{role_assignment}' => ['roles.manage', 'role-assignments.update'],
        'PATCH api/auth/role-assignments/{role_assignment}' => ['roles.manage', 'role-assignments.update'],
        'DELETE api/auth/role-assignments/{role_assignment}' => ['roles.manage', 'role-assignments.revoke'],
        'GET api/auth/role-revocations' => ['roles.manage', 'role-assignments.revoke'],
        'POST api/auth/role-revocations' => ['roles.manage', 'role-assignments.revoke'],
        'GET api/auth/role-revocations/{role_revocation}' => ['roles.manage', 'role-assignments.revoke'],

        // Menus / actions / modules
        'GET api/auth/menus' => ['roles.manage', 'menus.create', 'menus.update'],
        'POST api/auth/menus' => ['roles.manage', 'menus.create'],
        'GET api/auth/menus/{menu}' => ['roles.manage', 'menus.update'],
        'PUT api/auth/menus/{menu}' => ['roles.manage', 'menus.update'],
        'PATCH api/auth/menus/{menu}' => ['roles.manage', 'menus.update'],
        'DELETE api/auth/menus/{menu}' => ['roles.manage', 'menus.delete'],
        'POST api/auth/menu-items' => ['roles.manage', 'menus.create'],
        'GET api/auth/menu-items/{menu_item}' => ['roles.manage', 'menus.update'],
        'PUT api/auth/menu-items/{menu_item}' => ['roles.manage', 'menus.update'],
        'PATCH api/auth/menu-items/{menu_item}' => ['roles.manage', 'menus.update'],
        'DELETE api/auth/menu-items/{menu_item}' => ['roles.manage', 'menus.delete'],
        'POST api/auth/menu-roles' => ['roles.manage', 'menus.update'],
        'DELETE api/auth/menu-roles/{menu_role}' => ['roles.manage', 'menus.update'],
        'POST api/auth/menu-item-roles' => ['roles.manage', 'menus.update'],
        'DELETE api/auth/menu-item-roles/{menu_item_role}' => ['roles.manage', 'menus.update'],

        'GET api/auth/system-actions' => ['permissions.assign', 'actions.create', 'actions.update'],
        'POST api/auth/system-actions' => ['permissions.assign', 'actions.create'],
        'GET api/auth/system-actions/{system_action}' => ['permissions.assign', 'actions.update'],
        'PUT api/auth/system-actions/{system_action}' => ['permissions.assign', 'actions.update'],
        'PATCH api/auth/system-actions/{system_action}' => ['permissions.assign', 'actions.update'],
        'DELETE api/auth/system-actions/{system_action}' => ['permissions.assign', 'actions.delete'],
        'POST api/auth/system-action-roles' => ['permissions.assign', 'actions.update'],
        'DELETE api/auth/system-action-roles/{system_action_role}' => ['permissions.assign', 'actions.update'],
        'POST api/auth/user-system-actions' => ['permissions.assign', 'actions.update'],
        'DELETE api/auth/user-system-actions/{user_system_action}' => ['permissions.assign', 'actions.update'],

        'GET api/auth/system-modules' => ['roles.manage', 'workspaces.create', 'workspaces.update'],
        'POST api/auth/system-modules' => ['roles.manage', 'workspaces.create'],
        'GET api/auth/system-modules/{system_module}' => ['roles.manage', 'workspaces.update'],
        'PUT api/auth/system-modules/{system_module}' => ['roles.manage', 'workspaces.update'],
        'PATCH api/auth/system-modules/{system_module}' => ['roles.manage', 'workspaces.update'],
        'DELETE api/auth/system-modules/{system_module}' => ['roles.manage', 'workspaces.delete'],
        'POST api/auth/system-module-roles' => ['roles.manage', 'workspaces.update'],
        'DELETE api/auth/system-module-roles/{system_module_role}' => ['roles.manage', 'workspaces.update'],

        'GET api/auth/system-pages' => ['roles.manage', 'workspaces.update'],
        'POST api/auth/system-pages' => ['roles.manage', 'workspaces.create'],
        'GET api/auth/system-pages/{system_page}' => ['roles.manage', 'workspaces.update'],
        'PUT api/auth/system-pages/{system_page}' => ['roles.manage', 'workspaces.update'],
        'PATCH api/auth/system-pages/{system_page}' => ['roles.manage', 'workspaces.update'],
        'DELETE api/auth/system-pages/{system_page}' => ['roles.manage', 'workspaces.delete'],
        'POST api/auth/system-page-roles' => ['roles.manage', 'workspaces.update'],
        'DELETE api/auth/system-page-roles/{system_page_role}' => ['roles.manage', 'workspaces.update'],

        // Library settings (matrix)
        'GET api/settings/library' => ['settings.library.view'],
        'GET api/settings/library/history' => ['settings.library.view'],
        'PUT api/settings/library' => ['settings.library.update'],
        'DELETE api/settings/library/pending/{id}' => ['settings.library.update'],
        'POST api/settings/library/reset' => ['settings.library.reset'],

        // Provider credentials (secrets write-only)
        'GET api/settings/providers' => ['settings.providers'],
        'PUT api/settings/providers' => ['settings.providers'],

        // Branding (mutate only — GET is public)
        'POST api/settings/branding' => ['branding.manage', 'appearance.update'],
        'GET api/settings/login-slides/manage' => ['branding.manage', 'login-slides.create', 'login-slides.update'],
        'POST api/settings/login-slides' => ['branding.manage', 'login-slides.create'],
        'POST api/settings/login-slides/{loginSlide}' => ['branding.manage', 'login-slides.update'],
        'DELETE api/settings/login-slides/{loginSlide}' => ['branding.manage', 'login-slides.delete'],

        // Catalogue (matrix + legacy librarian.* )
        'GET api/book-catalogue/books' => ['catalogue.view', 'catalogue.manage', 'librarian.books.update', 'librarian.books.create'],
        'GET api/book-catalogue/books/summary' => ['catalogue.view', 'catalogue.manage', 'librarian.books.update'],
        'GET api/book-catalogue/books/{book}' => ['catalogue.view', 'catalogue.manage', 'librarian.books.update'],
        'GET api/book-catalogue/books/{book}/related' => ['catalogue.view', 'catalogue.manage', 'librarian.books.update'],
        'GET api/book-catalogue/books/{book}/read' => ['reading.read', 'catalogue.view'],
        'GET api/book-catalogue/books/{book}/pages/{page}' => ['reading.read', 'catalogue.view'],
        'GET api/book-catalogue/books/{book}/download' => ['catalogue.manage', 'librarian.books.update', 'reading.read'],
        'POST api/book-catalogue/books' => ['catalogue.manage', 'librarian.books.create'],
        'PUT api/book-catalogue/books/{book}' => ['catalogue.manage', 'librarian.books.update'],
        'PATCH api/book-catalogue/books/{book}' => ['catalogue.manage', 'librarian.books.update'],
        'DELETE api/book-catalogue/books/{book}' => ['catalogue.manage', 'librarian.books.delete'],
        'GET api/book-catalogue/authors' => ['catalogue.view', 'catalogue.manage', 'librarian.books.update'],
        'GET api/book-catalogue/categories' => ['catalogue.view', 'catalogue.manage', 'librarian.categories.update', 'librarian.categories.create'],
        'POST api/book-catalogue/categories' => ['catalogue.manage', 'librarian.categories.create'],
        'GET api/book-catalogue/categories/{category}' => ['catalogue.view', 'catalogue.manage', 'librarian.categories.update'],
        'PUT api/book-catalogue/categories/{category}' => ['catalogue.manage', 'librarian.categories.update'],
        'PATCH api/book-catalogue/categories/{category}' => ['catalogue.manage', 'librarian.categories.update'],
        'DELETE api/book-catalogue/categories/{category}' => ['catalogue.manage', 'librarian.categories.delete'],
        'GET api/book-catalogue/sub-categories' => ['catalogue.view', 'catalogue.manage', 'librarian.sub-categories.update'],
        'POST api/book-catalogue/sub-categories' => ['catalogue.manage', 'librarian.sub-categories.create'],
        'GET api/book-catalogue/sub-categories/{sub_category}' => ['catalogue.view', 'catalogue.manage', 'librarian.sub-categories.update'],
        'PUT api/book-catalogue/sub-categories/{sub_category}' => ['catalogue.manage', 'librarian.sub-categories.update'],
        'PATCH api/book-catalogue/sub-categories/{sub_category}' => ['catalogue.manage', 'librarian.sub-categories.update'],
        'DELETE api/book-catalogue/sub-categories/{sub_category}' => ['catalogue.manage', 'librarian.sub-categories.delete'],
        'GET api/book-catalogue/shelves' => ['catalogue.view', 'catalogue.manage', 'librarian.shelves.update'],
        'POST api/book-catalogue/shelves' => ['catalogue.manage', 'librarian.shelves.create'],
        'GET api/book-catalogue/shelves/{shelf}' => ['catalogue.view', 'catalogue.manage', 'librarian.shelves.update'],
        'PUT api/book-catalogue/shelves/{shelf}' => ['catalogue.manage', 'librarian.shelves.update'],
        'PATCH api/book-catalogue/shelves/{shelf}' => ['catalogue.manage', 'librarian.shelves.update'],
        'DELETE api/book-catalogue/shelves/{shelf}' => ['catalogue.manage', 'librarian.shelves.delete'],
        'GET api/book-catalogue/tags' => ['catalogue.view', 'catalogue.manage', 'librarian.tags.update'],
        'POST api/book-catalogue/tags' => ['catalogue.manage', 'librarian.tags.create'],
        'GET api/book-catalogue/tags/{tag}' => ['catalogue.view', 'catalogue.manage', 'librarian.tags.update'],
        'PUT api/book-catalogue/tags/{tag}' => ['catalogue.manage', 'librarian.tags.update'],
        'PATCH api/book-catalogue/tags/{tag}' => ['catalogue.manage', 'librarian.tags.update'],
        'DELETE api/book-catalogue/tags/{tag}' => ['catalogue.manage', 'librarian.tags.delete'],
        'GET api/book-catalogue/reusable-options' => ['catalogue.view', 'catalogue.manage', 'librarian.books.create', 'librarian.books.update'],
        'GET api/book-catalogue/favourites' => ['favourites.manage', 'catalogue.view'],
        'POST api/book-catalogue/favourites' => ['favourites.manage'],
        'DELETE api/book-catalogue/favourites/{book}' => ['favourites.manage'],
        'PATCH api/book-catalogue/physical-book-copies/{physicalBookCopy}/status' => ['copies.manage', 'librarian.books.update'],

        // Borrowing
        'GET api/book-borrowing/librarian/dashboard' => ['requests.decide', 'loans.issue', 'librarian.requests.approve'],
        'GET api/book-borrowing/requests' => ['requests.create', 'requests.decide', 'loans.view', 'librarian.requests.approve'],
        'POST api/book-borrowing/requests' => ['requests.create'],
        'GET api/book-borrowing/requests/{borrowing_request}' => ['requests.create', 'requests.decide', 'loans.view', 'librarian.requests.approve'],
        'DELETE api/book-borrowing/requests/{borrowing_request}' => ['requests.cancel', 'requests.decide', 'librarian.requests.delete'],
        'POST api/book-borrowing/requests/{borrowing_request}/approve' => ['requests.decide', 'librarian.requests.approve'],
        'POST api/book-borrowing/requests/{borrowing_request}/reject' => ['requests.decide', 'librarian.requests.reject'],
        'POST api/book-borrowing/requests/{borrowing_request}/mark-returned' => ['loans.return', 'librarian.requests.mark-returned'],
        'GET api/book-borrowing/processes' => ['borrowing-processes.create', 'borrowing-processes.update'],
        'POST api/book-borrowing/processes' => ['borrowing-processes.create'],
        'GET api/book-borrowing/processes/options' => ['borrowing-processes.create', 'borrowing-processes.update'],
        'GET api/book-borrowing/processes/{borrowing_process}' => ['borrowing-processes.update'],
        'PUT api/book-borrowing/processes/{borrowing_process}' => ['borrowing-processes.update'],
        'PATCH api/book-borrowing/processes/{borrowing_process}' => ['borrowing-processes.update'],
        'DELETE api/book-borrowing/processes/{borrowing_process}' => ['borrowing-processes.delete'],
        'GET api/book-borrowing/penalty-policies' => ['penalty-policies.create', 'penalty-policies.update', 'settings.library.view'],
        'POST api/book-borrowing/penalty-policies' => ['penalty-policies.create'],
        'GET api/book-borrowing/penalty-policies/{penalty_policy}' => ['penalty-policies.update'],
        'PUT api/book-borrowing/penalty-policies/{penalty_policy}' => ['penalty-policies.update'],
        'PATCH api/book-borrowing/penalty-policies/{penalty_policy}' => ['penalty-policies.update'],
        'DELETE api/book-borrowing/penalty-policies/{penalty_policy}' => ['penalty-policies.delete'],
        'GET api/book-borrowing/penalty-batches' => ['penalties.view', 'penalties.mark_paid', 'librarian.penalties.mark-paid'],
        'GET api/book-borrowing/penalty-batches/{penalty_batch}' => ['penalties.view', 'penalties.mark_paid', 'librarian.penalties.mark-paid'],
        'POST api/book-borrowing/penalty-batches/{penalty_batch}/mark-paid' => ['penalties.mark_paid', 'librarian.penalties.mark-paid'],
        'POST api/book-borrowing/penalty-batches/{penalty_batch}/stop' => ['penalties.forgive', 'librarian.penalties.stop'],

        // Academic master data (FR-USR-06)
        'GET api/academic/faculties' => [],
        'POST api/academic/faculties' => ['academic.manage'],
        'GET api/academic/faculties/{faculty}' => [],
        'PUT api/academic/faculties/{faculty}' => ['academic.manage'],
        'PATCH api/academic/faculties/{faculty}' => ['academic.manage'],
        'DELETE api/academic/faculties/{faculty}' => ['academic.manage'],
        'GET api/academic/departments' => [],
        'POST api/academic/departments' => ['academic.manage'],
        'GET api/academic/departments/{department}' => [],
        'PUT api/academic/departments/{department}' => ['academic.manage'],
        'PATCH api/academic/departments/{department}' => ['academic.manage'],
        'DELETE api/academic/departments/{department}' => ['academic.manage'],
        'GET api/academic/programmes' => [],
        'POST api/academic/programmes' => ['academic.manage'],
        'GET api/academic/programmes/{programme}' => [],
        'PUT api/academic/programmes/{programme}' => ['academic.manage'],
        'PATCH api/academic/programmes/{programme}' => ['academic.manage'],
        'DELETE api/academic/programmes/{programme}' => ['academic.manage'],
        'GET api/academic/courses' => [],
        'POST api/academic/courses' => ['academic.manage'],
        'GET api/academic/courses/{course}' => [],
        'PUT api/academic/courses/{course}' => ['academic.manage'],
        'PATCH api/academic/courses/{course}' => ['academic.manage'],
        'DELETE api/academic/courses/{course}' => ['academic.manage'],

        // Notifications (own inbox + preferences)
        'GET api/notifications' => [],
        'POST api/notifications/read-all' => [],
        'POST api/notifications/{notification}/read' => [],
        'GET api/notifications/preferences' => ['notifications.preferences'],
        'PUT api/notifications/preferences' => ['notifications.preferences'],

        // Reading
        'GET api/book-reading/member/dashboard' => ['reading.read', 'catalogue.view'],
        'GET api/book-reading/reading-histories' => ['reading.history.view', 'readings.export', 'librarian.readings.export'],
        'POST api/book-reading/reading-histories/track' => ['reading.read'],
        'GET api/book-reading/reading-histories/{reading_history}' => ['reading.history.view', 'readings.export', 'librarian.readings.export'],

        // Sanctum default user endpoint
        'GET api/user' => [],
    ],

    /**
     * Public (unauthenticated) API routes — method + path. Must not use permission middleware.
     *
     * @var list<string>
     */
    'public_routes' => [
        'POST api/auth/login',
        'POST api/auth/register',
        'POST api/auth/otp/request',
        'POST api/auth/otp/verify',
        'POST api/auth/password/forgot',
        'POST api/auth/password/verify-otp',
        'POST api/auth/password/reset',
        'GET api/settings/branding',
        'GET api/settings/login-slides',
        'GET api/settings/library/public',
        'GET up',
    ],
];
