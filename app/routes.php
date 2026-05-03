<?php

return [
    'GET' => [
        '/' => ['PageController', 'home'],
        '/teachers' => ['TeacherController', 'index'],
        '/pricing' => ['PageController', 'pricing'],
        '/login' => ['AuthController', 'login'],
        '/register' => ['AuthController', 'register'],
        '/logout' => ['AuthController', 'logout'],
        '/dashboard' => ['DashboardController', 'index'],
        '/messages' => ['ChatController', 'index'],
        '/teacher/profile' => ['TeacherController', 'edit'],
        '/teacher/classes' => ['ClassController', 'index'],
        '/admin' => ['AdminController', 'index'],
    ],
    'POST' => [
        '/login' => ['AuthController', 'authenticate'],
        '/register' => ['AuthController', 'store'],
        '/logout' => ['AuthController', 'destroy'],
        '/teachers/message' => ['ChatController', 'start'],
        '/messages/send' => ['ChatController', 'send'],
        '/classes/request' => ['ClassController', 'requestJoin'],
        '/teacher/profile' => ['TeacherController', 'update'],
        '/teacher/classes' => ['ClassController', 'store'],
        '/teacher/requests/update' => ['ClassController', 'updateRequest'],
        '/admin/educators/update' => ['AdminController', 'updateEducator'],
    ],
];
