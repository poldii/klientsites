<?php
declare(strict_types=1);

/**
 * Описание редактируемых полей. Админка и проверка при сохранении строятся отсюда.
 * type: text | textarea | url | image | lines (по строке на пункт) | list (повторяющиеся блоки)
 */
return [
    'site' => [
        'label' => 'Общее и контакты',
        'hint'  => 'Имя, контакты и описание для поисковиков (Яндекс, Google).',
        'fields' => [
            ['key' => 'name', 'label' => 'Ваше имя', 'type' => 'text'],
            ['key' => 'role', 'label' => 'Кто вы', 'type' => 'text'],
            ['key' => 'city', 'label' => 'Город', 'type' => 'text'],
            ['key' => 'title', 'label' => 'Заголовок вкладки браузера и поиска', 'type' => 'text'],
            ['key' => 'description', 'label' => 'Описание для поиска (1–2 предложения)', 'type' => 'textarea'],
            ['key' => 'phone', 'label' => 'Телефон', 'type' => 'text'],
            ['key' => 'email', 'label' => 'Email (на него придут заявки)', 'type' => 'text'],
            ['key' => 'telegram', 'label' => 'Ссылка на Telegram', 'type' => 'url', 'help' => 'Например: https://t.me/ваш_ник'],
            ['key' => 'whatsapp', 'label' => 'Ссылка на WhatsApp', 'type' => 'url', 'help' => 'Например: https://wa.me/79001234567'],
            ['key' => 'vk', 'label' => 'Ссылка на ВКонтакте', 'type' => 'url'],
        ],
    ],
    'hero' => [
        'label' => 'Первый экран',
        'hint'  => 'Самое первое, что видят гости сайта.',
        'fields' => [
            ['key' => 'kicker', 'label' => 'Строка над заголовком', 'type' => 'text'],
            ['key' => 'title_1', 'label' => 'Заголовок, слово 1', 'type' => 'text'],
            ['key' => 'title_2', 'label' => 'Заголовок, слово 2 (выделено цветом)', 'type' => 'text'],
            ['key' => 'title_3', 'label' => 'Заголовок, слово 3', 'type' => 'text'],
            ['key' => 'subtitle', 'label' => 'Подзаголовок', 'type' => 'textarea'],
            ['key' => 'cta', 'label' => 'Текст главной кнопки', 'type' => 'text'],
            ['key' => 'cta_secondary', 'label' => 'Текст второй кнопки', 'type' => 'text'],
            ['key' => 'badge', 'label' => 'Надпись на вращающейся наклейке', 'type' => 'text', 'help' => 'Короткие слова через точки, например: говорим • смеёмся • танцуем •'],
            ['key' => 'photo', 'label' => 'Главное фото', 'type' => 'image', 'help' => 'Лучше вертикальное, 4×5. JPG, PNG или WebP до 6 МБ.'],
            ['key' => 'photo_alt', 'label' => 'Подпись к фото (для незрячих и поиска)', 'type' => 'text'],
        ],
    ],
    'marquee' => [
        'label' => 'Бегущая строка',
        'hint'  => 'Типы праздников, которые вы ведёте.',
        'fields' => [
            ['key' => 'items', 'label' => 'Слова (каждое с новой строки)', 'type' => 'lines', 'max' => 14, 'flat' => true],
        ],
    ],
    'about' => [
        'label' => 'Обо мне',
        'hint'  => 'Коротко о вас и цифры.',
        'fields' => [
            ['key' => 'title', 'label' => 'Заголовок', 'type' => 'text'],
            ['key' => 'text_1', 'label' => 'Первый абзац (крупный)', 'type' => 'textarea'],
            ['key' => 'text_2', 'label' => 'Второй абзац', 'type' => 'textarea'],
            ['key' => 'photo', 'label' => 'Фото', 'type' => 'image'],
            ['key' => 'photo_alt', 'label' => 'Подпись к фото', 'type' => 'text'],
            ['key' => 'stats', 'label' => 'Цифры', 'type' => 'list', 'item_label' => 'Цифра', 'max' => 4, 'fields' => [
                ['key' => 'num', 'label' => 'Число', 'type' => 'text', 'max' => 6],
                ['key' => 'suffix', 'label' => 'Значок после числа (+, %, лет)', 'type' => 'text', 'max' => 8],
                ['key' => 'label', 'label' => 'Подпись', 'type' => 'text'],
            ]],
        ],
    ],
    'formats' => [
        'label' => 'Форматы праздников',
        'hint'  => 'Карточки, которые раскрываются по клику.',
        'fields' => [
            ['key' => 'title', 'label' => 'Заголовок раздела', 'type' => 'text'],
            ['key' => 'intro', 'label' => 'Вступление', 'type' => 'textarea'],
            ['key' => 'items', 'label' => 'Форматы', 'type' => 'list', 'item_label' => 'Формат', 'max' => 8, 'fields' => [
                ['key' => 'tag', 'label' => 'Номер (01, 02…)', 'type' => 'text', 'max' => 4],
                ['key' => 'title', 'label' => 'Название', 'type' => 'text'],
                ['key' => 'text', 'label' => 'Описание', 'type' => 'textarea'],
                ['key' => 'points', 'label' => 'Что входит (каждый пункт с новой строки)', 'type' => 'lines', 'max' => 8],
            ]],
        ],
    ],
    'timeline' => [
        'label' => 'Сценарий вечера',
        'hint'  => 'Как проходит праздник: время и события. На сайте часы пролистываются вместе с прокруткой.',
        'fields' => [
            ['key' => 'title', 'label' => 'Заголовок', 'type' => 'text'],
            ['key' => 'intro', 'label' => 'Вступление', 'type' => 'textarea'],
            ['key' => 'items', 'label' => 'Этапы вечера', 'type' => 'list', 'item_label' => 'Этап', 'max' => 10, 'fields' => [
                ['key' => 'time', 'label' => 'Время (например 19:30)', 'type' => 'text', 'max' => 8],
                ['key' => 'title', 'label' => 'Название этапа', 'type' => 'text'],
                ['key' => 'text', 'label' => 'Описание', 'type' => 'textarea'],
            ]],
        ],
    ],
    'reviews' => [
        'label' => 'Отзывы',
        'hint'  => 'Карточки, которые листаются вбок.',
        'fields' => [
            ['key' => 'title', 'label' => 'Заголовок', 'type' => 'text'],
            ['key' => 'items', 'label' => 'Отзывы', 'type' => 'list', 'item_label' => 'Отзыв', 'max' => 12, 'fields' => [
                ['key' => 'name', 'label' => 'Имя автора', 'type' => 'text'],
                ['key' => 'event', 'label' => 'Какой был праздник', 'type' => 'text'],
                ['key' => 'text', 'label' => 'Текст отзыва', 'type' => 'textarea'],
            ]],
        ],
    ],
    'process' => [
        'label' => 'Как мы работаем',
        'hint'  => 'Шаги от знакомства до праздника.',
        'fields' => [
            ['key' => 'title', 'label' => 'Заголовок', 'type' => 'text'],
            ['key' => 'items', 'label' => 'Шаги', 'type' => 'list', 'item_label' => 'Шаг', 'max' => 6, 'fields' => [
                ['key' => 'title', 'label' => 'Название шага', 'type' => 'text'],
                ['key' => 'text', 'label' => 'Описание', 'type' => 'textarea'],
            ]],
        ],
    ],
    'contact' => [
        'label' => 'Контакты и форма заявки',
        'hint'  => 'Последний блок сайта.',
        'fields' => [
            ['key' => 'title', 'label' => 'Заголовок', 'type' => 'text'],
            ['key' => 'text', 'label' => 'Текст', 'type' => 'textarea'],
            ['key' => 'form_button', 'label' => 'Надпись на кнопке формы', 'type' => 'text'],
            ['key' => 'form_success', 'label' => 'Сообщение после отправки', 'type' => 'text'],
            ['key' => 'privacy', 'label' => 'Мелкий текст про персональные данные', 'type' => 'text'],
        ],
    ],
];
