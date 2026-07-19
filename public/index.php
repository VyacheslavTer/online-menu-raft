<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$locale = current_locale();
$locales = supported_locales();
$localeMeta = $locales[$locale];
$ui = [
    'ru' => [
        'current_language' => 'Текущий язык',
        'choose_now' => 'Выберите то, что хочется сейчас',
        'search' => 'Поиск',
        'top_menus' => 'Основные меню',
        'menu_sections' => 'Разделы меню',
        'back' => 'Назад',
        'more_in_section' => 'Еще в разделе',
        'empty' => 'Скоро здесь появятся позиции меню.',
        'add' => 'Добавить',
        'remove' => 'Убрать',
        'scroll_top' => 'Наверх',
        'open_receipt' => 'Открыть чек',
        'your_order' => 'Ваш заказ',
        'close_receipt' => 'Закрыть чек',
        'close' => 'Закрыть',
        'total' => 'Итого',
        'socials' => 'Соцсети',
    ],
    'kz' => [
        'current_language' => 'Ағымдағы тіл',
        'choose_now' => 'Қазір қалағаныңызды таңдаңыз',
        'search' => 'Іздеу',
        'top_menus' => 'Негізгі мәзірлер',
        'menu_sections' => 'Мәзір бөлімдері',
        'back' => 'Артқа',
        'more_in_section' => 'Осы бөлімде тағы',
        'empty' => 'Жақында мұнда мәзір позициялары пайда болады.',
        'add' => 'Қосу',
        'remove' => 'Алу',
        'scroll_top' => 'Жоғары',
        'open_receipt' => 'Чекті ашу',
        'your_order' => 'Сіздің тапсырысыңыз',
        'close_receipt' => 'Чекті жабу',
        'close' => 'Жабу',
        'total' => 'Барлығы',
        'socials' => 'Әлеуметтік желілер',
    ],
    'en' => [
        'current_language' => 'Current language',
        'choose_now' => 'Choose what you want now',
        'search' => 'Search',
        'top_menus' => 'Main menus',
        'menu_sections' => 'Menu sections',
        'back' => 'Back',
        'more_in_section' => 'More in this section',
        'empty' => 'Menu items will appear here soon.',
        'add' => 'Add',
        'remove' => 'Remove',
        'scroll_top' => 'Back to top',
        'open_receipt' => 'Open check',
        'your_order' => 'Your order',
        'close_receipt' => 'Close check',
        'close' => 'Close',
        'total' => 'Total',
        'socials' => 'Social media',
    ],
][$locale];

$repo = new MenuRepository(db(), $locale);
$settings = new Settings(db());

$id = isset($_GET['id']) ? max(0, (int) $_GET['id']) : null;
$current = $id ? $repo->find($id) : null;

if ($id && (!$current || (int) $current['is_active'] !== 1)) {
    http_response_code(404);
    exit('Раздел не найден.');
}

$children = $repo->children($current ? (int) $current['id'] : null, true);
$topLevel = $repo->children(null, true);
$breadcrumbs = $current ? $repo->breadcrumbs((int) $current['id']) : [];
$siteName = localized_setting($settings, 'site_name', $locale, (string) config('app.name', 'raft'));
$siteSubtitle = localized_setting($settings, 'site_subtitle', $locale, (string) config('app.subtitle', 'Онлайн меню'));
$contacts = localized_setting($settings, 'contacts', $locale);
$hours = localized_setting($settings, 'working_hours', $locale);
$faviconPath = $settings->get('favicon_path');
$socialLinks = array_values(array_filter([
    ['label' => 'Instagram', 'short' => 'IG', 'icon' => 'instagram', 'url' => external_url($settings->get('instagram_url'))],
    ['label' => 'Telegram', 'short' => 'TG', 'icon' => 'telegram', 'url' => external_url($settings->get('telegram_url'))],
    ['label' => 'WhatsApp', 'short' => 'WA', 'icon' => 'whatsapp', 'url' => external_url($settings->get('whatsapp_url'))],
], static fn (array $link): bool => $link['url'] !== ''));

$currentHasChildren = $current && count($children) > 0;
$isDetail = $current && !$currentHasChildren && (string) ($current['item_type'] ?? 'item') === 'item';
$activeTop = $breadcrumbs[0] ?? ($topLevel[0] ?? null);
$activeTopId = $activeTop ? (int) $activeTop['id'] : null;
$sections = $activeTopId ? $repo->children($activeTopId, true) : [];
$activeSection = null;

if (count($breadcrumbs) > 1) {
    $activeSection = $breadcrumbs[1];
} elseif ($current && $activeTopId && (int) $current['id'] !== $activeTopId && !$isDetail) {
    $activeSection = $current;
} elseif ($sections) {
    $activeSection = $sections[0];
}

$sectionGroups = [];
foreach ($sections as $section) {
    $items = $repo->children((int) $section['id'], true);
    if (!$items) {
        continue;
    }
    $sectionGroups[] = [
        'section' => $section,
        'items' => $items,
    ];
}

$displayItems = [];
if ($isDetail && $activeSection) {
    $displayItems = array_values(array_filter(
        $repo->children((int) $activeSection['id'], true),
        static fn (array $item): bool => !$current || (int) $item['id'] !== (int) $current['id']
    ));
}

$sectionTitle = $isDetail ? $ui['more_in_section'] : (string) ($activeTop['title'] ?? $siteSubtitle);
$promoImage = asset_url($activeTop['image_path'] ?? 'uploads/menu-source/food-02.jpg');
$activeTopHref = menu_url($activeTopId, $locale);
$initialSectionId = $activeSection ? (int) $activeSection['id'] : (int) ($sections[0]['id'] ?? 0);
$cssVersion = (string) filemtime(__DIR__ . '/assets/app.css');
$jsVersion = (string) filemtime(__DIR__ . '/assets/app.js');
$renderDishRow = static function (array $item) use ($locale, $ui): void {
    $hasImage = !empty($item['image_path']);
    $rowClass = 'dish-row' . ($hasImage ? ' has-image' : '');
    $priceLabel = money($item['price'] ?? '');
    $firstPrice = preg_replace('/\/.*$/u', '', $priceLabel) ?? '';
    $priceAmount = (int) preg_replace('/\D+/', '', $firstPrice);
    $imageUrl = $hasImage ? asset_url($item['image_path']) : '';
    ?>
    <article class="<?= e($rowClass) ?>" data-menu-card data-cart-item data-cart-id="<?= (int) $item['id'] ?>" data-cart-title="<?= e($item['title']) ?>" data-cart-price-label="<?= e($priceLabel) ?>" data-cart-price-amount="<?= $priceAmount ?>" data-cart-image="<?= e($imageUrl) ?>">
        <a class="dish-row__link" href="<?= e(menu_url((int) $item['id'], $locale)) ?>">
            <?php if ($imageUrl): ?>
                <img class="dish-row__image" src="<?= e($imageUrl) ?>" alt="">
            <?php endif; ?>
            <span class="dish-row__content">
                <span class="dish-row__title"><?= e($item['title']) ?></span>
                <?php if (!empty($item['description'])): ?>
                    <span class="dish-row__description"><?= nl2br(e($item['description'])) ?></span>
                <?php endif; ?>
                <?php if ($priceLabel !== ''): ?>
                    <span class="dish-row__price"><?= e($priceLabel) ?></span>
                <?php endif; ?>
            </span>
        </a>
        <span class="dish-row__cart" data-cart-controls>
            <button class="dish-row__counter" type="button" data-cart-remove aria-label="<?= e($ui['remove']) ?>" hidden>-</button>
            <span class="dish-row__qty" data-cart-qty hidden>0</span>
            <button class="dish-row__action" type="button" data-cart-add aria-label="<?= e($ui['add']) ?>">+</button>
        </span>
    </article>
    <?php
};

?><!doctype html>
<html lang="<?= e($localeMeta['html']) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($current['title'] ?? $siteName) ?> · <?= e($siteSubtitle) ?></title>
    <?php if ($faviconPath): ?>
        <link rel="icon" href="<?= e(asset_url($faviconPath)) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="/assets/app.css?v=<?= e($cssVersion) ?>">
    <script>window.menuI18n = <?= json_encode(['locale' => $locale, 'numberLocale' => $locale === 'en' ? 'en-US' : 'ru-RU', 'add' => $ui['add'], 'remove' => $ui['remove']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>
    <script src="/assets/app.js?v=<?= e($jsVersion) ?>" defer></script>
</head>
<body class="menu-page" data-locale="<?= e($locale) ?>">
    <main class="menu-app">
        <header class="menu-header">
            <div class="restaurant-titlebar">
                <a class="restaurant-brand" href="<?= e(menu_url(null, $locale)) ?>">
                    <span class="restaurant-brand__name"><?= e($siteName) ?></span>
                </a>

                <?php if ($socialLinks): ?>
                    <nav class="restaurant-socials" aria-label="<?= e($ui['socials']) ?>">
                        <?php foreach ($socialLinks as $link): ?>
                            <a class="restaurant-social-icon restaurant-social-icon--<?= e($link['icon']) ?>" href="<?= e($link['url']) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e($link['label']) ?>" title="<?= e($link['label']) ?>">
                                <?php if ($link['icon'] === 'instagram'): ?>
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="5"/><circle cx="12" cy="12" r="3.5"/><circle cx="17" cy="7" r="1"/></svg>
                                <?php else: ?>
                                    <span><?= e($link['short']) ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                <?php endif; ?>

                <nav class="language-switcher" aria-label="<?= e($ui['current_language']) ?>">
                    <?php foreach ($locales as $code => $meta): ?>
                        <a class="language-pill<?= $code === $locale ? ' is-active' : '' ?>" href="<?= e(menu_url($id, $code, '', true)) ?>" hreflang="<?= e($meta['html']) ?>" lang="<?= e($meta['html']) ?>"><?= e($meta['label']) ?></a>
                    <?php endforeach; ?>
                </nav>
            </div>

            <?php if ($contacts || $hours): ?>
                <div class="restaurant-meta">
                    <?php if ($hours): ?><span><?= e($hours) ?></span><?php endif; ?>
                    <?php if ($contacts): ?><span><?= e($contacts) ?></span><?php endif; ?>
                </div>
            <?php endif; ?>


        </header>

        <section class="menu-promo" aria-label="<?= e($siteSubtitle) ?>">
            <div class="menu-promo__copy">
                <p><?= e($siteSubtitle) ?></p>
                <strong><?= e($ui['choose_now']) ?></strong>
                <span>raft kitchen & bar</span>
            </div>
            <?php if ($promoImage): ?>
                <img src="<?= e($promoImage) ?>" alt="">
            <?php endif; ?>
        </section>

        <label class="menu-search">
            <span class="menu-search__icon" aria-hidden="true">⌕</span>
            <input type="search" data-menu-search placeholder="<?= e($ui['search']) ?>">
        </label>

        <?php if ($topLevel): ?>
            <nav class="menu-switcher" aria-label="<?= e($ui['top_menus']) ?>">
                <?php foreach ($topLevel as $index => $tab): ?>
                    <?php
                    $isActiveTop = $activeTopId !== null && (int) $tab['id'] === $activeTopId;
                    $fallbackImage = $index % 2 === 0 ? 'uploads/menu-source/food-01.jpg' : 'uploads/menu-source/bar-drinks-01.jpg';
                    $tabImage = asset_url($tab['image_path'] ?: $fallbackImage);
                    ?>
                    <a class="menu-switcher__card<?= $isActiveTop ? ' is-active' : '' ?>" href="<?= e(menu_url((int) $tab['id'], $locale)) ?>">
                        <?php if ($tabImage): ?>
                            <img src="<?= e($tabImage) ?>" alt="">
                        <?php endif; ?>
                        <span><?= e($tab['title']) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <?php if ($sections): ?>
            <nav class="category-strip" aria-label="<?= e($ui['menu_sections']) ?>" data-category-strip data-initial-section-id="<?= $initialSectionId ?>">
                <?php foreach ($sections as $section): ?>
                    <?php
                    $isActiveSection = $activeSection && (int) $section['id'] === (int) $activeSection['id'];
                    $sectionHash = '#category-' . (int) $section['id'];
                    $sectionHref = $isDetail ? $activeTopHref . $sectionHash : $sectionHash;
                    ?>
                    <a class="category-chip<?= $isActiveSection ? ' is-active' : '' ?>" href="<?= e($sectionHref) ?>" data-category-link data-section-id="<?= (int) $section['id'] ?>"><?= e($section['title']) ?></a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <?php if ($isDetail && $current): ?>
            <article class="dish-detail">
                <?php if (!empty($current['image_path'])): ?>
                    <img src="<?= e(asset_url($current['image_path'])) ?>" alt="">
                <?php endif; ?>
                <div class="dish-detail__body">
                    <a class="dish-detail__back" href="<?= e($activeTopHref . ($activeSection ? '#category-' . (int) $activeSection['id'] : '')) ?>"><?= e($ui['back']) ?></a>
                    <h1><?= e($current['title']) ?></h1>
                    <?php if (!empty($current['description'])): ?>
                        <p><?= nl2br(e($current['description'])) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($current['price'])): ?>
                        <strong><?= e(money($current['price'])) ?></strong>
                    <?php endif; ?>
                </div>
            </article>
        <?php endif; ?>

        <?php if ($isDetail): ?>
            <section class="dish-section" id="menu-content">
                <h1><?= e($sectionTitle) ?></h1>

                <?php if (!$displayItems): ?>
                    <p class="empty-state"><?= e($ui['empty']) ?></p>
                <?php endif; ?>

                <div class="dish-list" data-menu-list>
                    <?php foreach ($displayItems as $item): ?>
                        <?php $renderDishRow($item); ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php else: ?>
            <div class="menu-sections" data-menu-sections>
                <?php foreach ($sectionGroups as $group): ?>
                    <?php $section = $group['section']; ?>
                    <section class="dish-section" id="category-<?= (int) $section['id'] ?>" data-menu-section data-section-id="<?= (int) $section['id'] ?>">
                        <h1><?= e($section['title']) ?></h1>
                        <div class="dish-list" data-menu-list>
                            <?php foreach ($group['items'] as $item): ?>
                                <?php $renderDishRow($item); ?>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>

                <?php if (!$sectionGroups): ?>
                    <p class="empty-state"><?= e($ui['empty']) ?></p>
                <?php endif; ?>

            </div>
        <?php endif; ?>
    </main>

    <button class="scroll-top-button" type="button" data-scroll-top aria-label="<?= e($ui['scroll_top']) ?>" hidden>
        <span aria-hidden="true"></span>
    </button>

    <button class="cart-summary" type="button" data-cart-summary hidden aria-label="<?= e($ui['open_receipt']) ?>">
        <span class="cart-summary__icon" aria-hidden="true"></span>
        <span data-cart-summary-total>0 ₸</span>
    </button>

    <div class="cart-sheet" data-cart-sheet hidden role="dialog" aria-modal="true" aria-label="<?= e($ui['your_order']) ?>">
        <button class="cart-sheet__backdrop" type="button" data-cart-close aria-label="<?= e($ui['close_receipt']) ?>"></button>
        <section class="cart-sheet__panel">
            <div class="cart-sheet__header">
                <h2><?= e($ui['your_order']) ?></h2>
                <button type="button" data-cart-close aria-label="<?= e($ui['close']) ?>">x</button>
            </div>
            <div class="cart-sheet__list" data-cart-list></div>
            <div class="cart-sheet__total">
                <span><?= e($ui['total']) ?></span>
                <strong data-cart-total>0 ₸</strong>
            </div>

        </section>
    </div>
</body>
</html>