<?php

declare(strict_types=1);

final class SeedData
{
    public static function items(): array
    {
        return [
            [
                'title' => 'Основное меню',
                'type' => 'section',
                'description' => 'Черновая структура по фото бумажного меню. Позиции и цены можно уточнить в админке.',
                'image' => 'uploads/menu-source/food-01.jpg',
                'children' => [
                    [
                        'title' => 'Салаты',
                        'type' => 'section',
                        'children' => [
                            ['title' => 'Пикантный салат с хрустящими баклажанами с мисо чили', 'description' => 'С соусом свит чили.'],
                            ['title' => 'Битые огурцы с чили перцем'],
                            ['title' => 'Салат из свежих овощей с греческим йогуртом и гриль'],
                            ['title' => 'Салат с лососем и авокадо с цитрусовой заправкой'],
                            ['title' => 'Хрустящие листья салата Цезарь с гренками', 'description' => 'Вариант с курицей.'],
                            ['title' => 'Хрустящие листья салата Цезарь с креветками'],
                            ['title' => 'Тайский салат с мясом кунжутной заправкой и мятой'],
                            ['title' => 'Салат из печеной свеклы, апельсина и крем бальзамика'],
                        ],
                    ],
                    [
                        'title' => 'Закуски холодные',
                        'type' => 'section',
                        'children' => [
                            ['title' => 'Малосольный лосось, голец'],
                            ['title' => 'Чечел'],
                            ['title' => 'Чечел жаренный'],
                            ['title' => 'Луковые кольца'],
                        ],
                    ],
                    [
                        'title' => 'Горячие закуски',
                        'type' => 'section',
                        'children' => [
                            ['title' => 'Золотистые жареные креветки пивные 400 гр.', 'description' => 'Пивные.'],
                            ['title' => 'Сырные палочки с копченой сметаной'],
                        ],
                    ],
                    [
                        'title' => 'Первые блюда',
                        'type' => 'section',
                        'children' => [
                            ['title' => 'Острый суп Том Ям', 'description' => 'Рис, лосось, судак, грибы шиитаке.', 'price' => '2 950 ₸ / 2 950 ₸'],
                            ['title' => 'Суп чечевичный', 'price' => '2 800 ₸'],
                        ],
                    ],
                    [
                        'title' => 'Вторые блюда',
                        'type' => 'section',
                        'children' => [
                            ['title' => 'Отбивная куриная грудка венский шницель', 'description' => 'Панированная в сухарях, с грибным соусом.', 'price' => '3 650 ₸'],
                            ['title' => 'Дымные томленные ребрышки с соусом харчо', 'description' => 'Говяжье с пюре из чечевицы и сальсой.', 'price' => '6 500 ₸ / 8 900 ₸'],
                            ['title' => 'Стейк Амиго', 'description' => 'С гранатовым соусом.', 'price' => '7 200 ₸'],
                            ['title' => 'Пепер стейк', 'price' => '9 000 ₸'],
                            ['title' => 'Буглома "Степняк"', 'price' => '4 500 ₸'],
                            ['title' => 'Медальон из лосося со спаржей и шпинатом', 'price' => '6 500 ₸'],
                        ],
                    ],
                    [
                        'title' => 'Шашлыки',
                        'type' => 'section',
                        'children' => [
                            ['title' => 'Шашлык из баранины', 'description' => 'Лаваш, соус, запеченный картофель, маринованный лук.'],
                            ['title' => 'Шашлык из куриного филе', 'description' => 'Лаваш, соус, запеченный картофель, маринованный лук.'],
                            ['title' => 'Люля-кебаб', 'description' => 'Лаваш, соус, запеченный картофель, маринованный лук.'],
                            ['title' => 'Шашлык из утиной грудки'],
                            ['title' => 'Ассорти из шашлыков 5', 'description' => 'Баранина, говядина, утка, курица.'],
                        ],
                    ],
                    [
                        'title' => 'Гарниры',
                        'type' => 'section',
                        'children' => [
                            ['title' => 'Золотистый картофель фри'],
                            ['title' => 'Золотистые картофельные дольки'],
                            ['title' => 'Картофель по-домашнему'],
                            ['title' => 'Ароматные овощи гриль'],
                            ['title' => 'Ароматный восточный рис'],
                        ],
                    ],
                    [
                        'title' => 'Паста',
                        'type' => 'section',
                        'children' => [
                            ['title' => 'Аппетитная паста карбонара с говяжьим беконом', 'description' => 'На выбор бекон или копченая телятина, черный, желток, сыр пармезан.'],
                            ['title' => 'Лингвини с курицей и грибами'],
                        ],
                    ],
                    [
                        'title' => 'Хлеб',
                        'type' => 'section',
                        'children' => [
                            ['title' => 'Хлеб ассорти', 'price' => '1 350 ₸'],
                        ],
                    ],
                    [
                        'title' => 'Пицца мясная',
                        'type' => 'section',
                        'children' => [
                            ['title' => 'Пицца Маргарита', 'price' => '3 200 ₸'],
                            ['title' => 'Пицца Ранч с цыпленком', 'price' => '3 550 ₸'],
                        ],
                    ],
                    [
                        'title' => 'Пицца',
                        'type' => 'section',
                        'children' => [
                            ['title' => 'Пицца Пикантная пепперони', 'price' => '3 650 ₸'],
                            ['title' => 'Пицца Болоньезе', 'price' => '3 550 ₸'],
                            ['title' => 'Пицца Капричоза', 'price' => '3 850 ₸'],
                        ],
                    ],
                    [
                        'title' => 'Соусы',
                        'type' => 'section',
                        'children' => [
                            ['title' => 'Кетчуп', 'price' => '490 ₸'],
                            ['title' => 'Шашлычный', 'price' => '490 ₸'],
                            ['title' => 'Горчица', 'price' => '490 ₸'],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Барное меню',
                'type' => 'section',
                'description' => 'Напитки, чай, кофе, коктейли и барные позиции.',
                'image' => 'uploads/menu-source/bar-drinks-01.jpg',
                'children' => [
                    [
                        'title' => 'Эспрессо напитки, коктейли',
                        'type' => 'section',
                        'children' => [
                            ['title' => 'Эспрессо', 'description' => '1 порция.', 'price' => '900 ₸'],
                            ['title' => 'Американо', 'price' => '1 100 ₸'],
                            ['title' => 'Капучино', 'price' => '1 100 ₸'],
                            ['title' => 'Латте', 'price' => '1 400 ₸'],
                            ['title' => 'Латте Макиато', 'price' => '1 600 ₸'],
                        ],
                    ],
                    [
                        'title' => 'Лимонады',
                        'type' => 'section',
                        'children' => [
                            ['title' => 'Тропический', 'description' => '1 л / 0.33. Сочный вкус фруктов и цитрусов.'],
                            ['title' => 'Манго Маракуйя', 'description' => '0.33. Сочный манго и ароматная маракуйя.'],
                            ['title' => 'Киви Лайм', 'description' => '0.33. Экзотический дуэт киви и лайма.'],
                            ['title' => 'Mojito', 'description' => '0.33. Лайм, мята и газированная основа.'],
                            ['title' => 'Ягодный Микс', 'description' => '1 л / 0.33. Ассорти ягод.'],
                        ],
                    ],
                    [
                        'title' => 'Газированные напитки',
                        'type' => 'section',
                        'children' => [
                            ['title' => 'Coca-Cola', 'description' => '1 л / 0.5.'],
                            ['title' => 'Fanta', 'description' => '1 л / 0.33.'],
                        ],
                    ],
                    [
                        'title' => 'Соки, минеральная вода',
                        'type' => 'section',
                        'children' => [
                            ['title' => 'Gracio в ассортименте', 'description' => '1 л / 0.25.'],
                            ['title' => 'Tassay', 'description' => '0.5 / 1 л.', 'price' => '750 ₸ / 1 250 ₸'],
                            ['title' => 'Апельсиновый фреш', 'price' => '2 700 ₸'],
                        ],
                    ],
                    [
                        'title' => 'Зеленый чай',
                        'type' => 'section',
                        'children' => [
                            ['title' => 'Green Tea', 'price' => '1 550 ₸'],
                        ],
                    ],
                    [
                        'title' => 'Авторские чаи',
                        'type' => 'section',
                        'children' => [
                            ['title' => 'Ташкентский чай', 'description' => '0.25 / 0.8.', 'price' => '1 000 ₸ / 2 250 ₸'],
                            ['title' => 'Облепиховый чай', 'description' => '0.25 / 0.8.', 'price' => '1 000 ₸ / 2 250 ₸'],
                            ['title' => 'Малиновый чай (бабушкин рецепт)', 'description' => '0.25 / 0.8.', 'price' => '1 000 ₸ / 2 250 ₸'],
                        ],
                    ],
                    [
                        'title' => 'Чай в ассортименте',
                        'type' => 'section',
                        'children' => [
                            ['title' => 'Зеленый чай чашка / Черный чай чашка', 'price' => '450 ₸'],
                        ],
                    ],
                    [
                        'title' => 'К чаю',
                        'type' => 'section',
                        'children' => [
                            ['title' => 'Молоко', 'price' => '300 ₸'],
                            ['title' => 'Лимон', 'price' => '850 ₸'],
                        ],
                    ],
                    [
                        'title' => 'Настойки',
                        'type' => 'section',
                        'children' => [
                            ['title' => 'Настойка на выбор', 'price' => '1 200 ₸'],
                            ['title' => 'Алтайская водка', 'description' => 'Настойка: сок облепиховый, сок яблочный, порошок пантов марала, экстракт трав Алтая.', 'price' => '1 500 ₸'],
                        ],
                    ],
                    [
                        'title' => 'Алкогольные коктейли',
                        'type' => 'section',
                        'children' => [
                            ['title' => 'LONG ISLAND ICE TEA', 'price' => '3 400 ₸'],
                            ['title' => 'MOJITO', 'price' => '2 650 ₸'],
                            ['title' => 'SEX ON THE BEACH', 'price' => '2 700 ₸'],
                            ['title' => 'PINA COLADA', 'price' => '3 500 ₸'],
                            ['title' => 'TEQUILA SUNRISE', 'price' => '2 500 ₸'],
                            ['title' => 'GIN-TONIC', 'price' => '2 750 ₸'],
                            ['title' => 'APEROL SPRITZ', 'price' => '3 500 ₸'],
                        ],
                    ],
                    [
                        'title' => 'Пиво разливное',
                        'type' => 'section',
                        'children' => [
                            ['title' => 'Karloff (фильтрованное)', 'description' => '1 литр / 3 литра. Мягкое натуральное пиво.', 'price' => '1 500 ₸ / 3 000 ₸ / 8 900 ₸'],
                            ['title' => 'BIRHOFF Томаш Полутемное', 'price' => '1 600 ₸'],
                        ],
                    ],
                    [
                        'title' => 'Пиво бутылочное',
                        'type' => 'section',
                        'children' => [
                            ['title' => 'MILLER', 'price' => '1 950 ₸'],
                            ['title' => 'EFES 0', 'price' => '1 500 ₸'],
                        ],
                    ],
                    [
                        'title' => 'Аперитив / Вермут / Биттер',
                        'type' => 'section',
                        'children' => [
                            ['title' => 'APEROL', 'price' => '2 100 ₸'],
                            ['title' => 'MARTINI BIANCO', 'price' => '2 350 ₸'],
                            ['title' => 'JAGERMEISTER', 'price' => '2 300 ₸'],
                        ],
                    ],
                    [
                        'title' => 'Коньяк',
                        'type' => 'section',
                        'children' => [
                            ['title' => 'HENNESSY VS', 'price' => '4 500 ₸'],
                            ['title' => 'АРАРАТ 3', 'price' => '2 000 ₸'],
                            ['title' => 'АРАРАТ 5', 'price' => '2 800 ₸'],
                        ],
                    ],
                    [
                        'title' => 'Виски',
                        'type' => 'section',
                        'children' => [
                            ['title' => 'JOHNIE WALKER RED LABEL', 'price' => '1 700 ₸'],
                            ['title' => 'DEWARS WHITE LABEL', 'price' => '1 700 ₸'],
                            ['title' => 'DEWARS 12 Y.O.', 'price' => '3 400 ₸'],
                            ['title' => 'BALLANTINES', 'price' => '1 800 ₸'],
                            ['title' => 'MACALLAN 12 Y.O.', 'price' => '8 200 ₸'],
                        ],
                    ],
                    [
                        'title' => 'Сигареты, стики, другое',
                        'type' => 'section',
                        'children' => [
                            ['title' => 'Сигареты', 'description' => 'Parlament / Chapman.', 'price' => '2 000 ₸ / 2 500 ₸'],
                        ],
                    ],
                ],
            ],
        ];
    }
}

