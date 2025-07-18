<?php
global $hesk_settings, $hesklang;

// This guard is used to ensure that users can't hit this outside of actual HESK code
if (!defined('IN_SCRIPT')) {
    die();
}

/**
 * @var array $topArticles - Collection of top knowledgebase articles
 * @var array $latestArticles - Collection of newest/latest knowledgebase articles
 * @var array $serviceMessages - Collection of service messages to be displayed
 * @var array $messages - Collection of feedback messages to be displayed (such as "You have been logged out")
 * @var bool $accountRequired - `true` if an account is required to use the helpdesk, `false` otherwise
 * @var bool $customerLoggedIn - `true` if a customer is logged in, `false` otherwise
 * @var array $customerUserContext - User info for a customer if logged in.  `null` if a customer is not logged in.
 */

require_once(TEMPLATE_PATH . 'customer/util/alerts.php');
require_once(TEMPLATE_PATH . 'customer/util/kb-search.php');
require_once(TEMPLATE_PATH . 'customer/util/rating.php');
require_once(TEMPLATE_PATH . 'customer/partial/login-navbar-elements.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title><?php echo $hesk_settings['hesk_title']; ?></title>
    <meta http-equiv="X-UA-Compatible" content="IE=Edge" />
    <meta name="viewport" content="width=device-width,minimum-scale=1.0,maximum-scale=1.0" />
    <?php include(HESK_PATH . 'inc/favicon.inc.php'); ?>
    <meta name="format-detection" content="telephone=no" />
    <link rel="stylesheet" media="all" href="<?php echo TEMPLATE_PATH; ?>customer/css/app<?php echo $hesk_settings['debug_mode'] ? '' : '.min'; ?>.css?<?php echo $hesk_settings['hesk_version']; ?>" />
    <!--[if IE]>
    <link rel="stylesheet" media="all" href="<?php echo TEMPLATE_PATH; ?>customer/css/ie9.css" />
    <![endif]-->
    <style>
        <?php outputSearchStyling(); ?>
    </style>
    <?php include(TEMPLATE_PATH . '../../head.txt'); ?>
</head>

<body class="cust-help">
<?php include(TEMPLATE_PATH . '../../header.txt'); ?>
<?php renderCommonElementsAfterBody(); ?>
<div class="wrapper">
    <main class="main" id="maincontent">
        <header class="header">
            <div class="contr">
                <div class="header__inner">
                    <a href="<?php echo $hesk_settings['hesk_url']; ?>" class="header__logo">
                        <?php echo $hesk_settings['hesk_title']; ?>
                    </a>
                    <?php renderLoginNavbarElements($customerUserContext); ?>
                    <?php renderNavbarLanguageSelect(); ?>
                </div>
            </div>
        </header>
        <div class="breadcrumbs">
            <div class="contr">
                <div class="breadcrumbs__inner">
                    <a href="<?php echo $hesk_settings['site_url']; ?>">
                        <span><?php echo $hesk_settings['site_title']; ?></span>
                    </a>
                    <svg class="icon icon-chevron-right">
                        <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-chevron-right"></use>
                    </svg>
                    <div class="last"><?php echo $hesk_settings['hesk_title']; ?></div>
                </div>
            </div>
        </div>
        <div class="main__content">
            <div class="contr">
                <div style="margin-bottom: 20px;">
                    <?php hesk3_show_messages($messages); ?>
                </div>
                <div class="help-search">
                    <h1 class="search__title"><?php echo $hesklang['how_can_we_help']; ?></h1>
                    <?php displayKbSearch(); ?>
                </div>
                <?php hesk3_show_messages($serviceMessages); ?>
                <div class="nav">
                    <a href="index.php?a=add" class="navlink">
                        <span class="icon-in-circle" aria-hidden="true">
                            <svg class="icon icon-submit-ticket">
                                <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-submit-ticket"></use>
                            </svg>
                        </span>
                        <div>
                            <h3 class="navlink__title"><?php echo $hesklang['submit_ticket']; ?></h3>
                            <div class="navlink__descr"><?php echo $hesklang['open_ticket']; ?></div>
                        </div>
                    </a>
                    <?php if ($accountRequired || $customerLoggedIn): ?>
                    <a href="my_tickets.php" class="navlink">
                        <span class="icon-in-circle" aria-hidden="true">
                            <svg class="icon icon-document">
                                <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-document"></use>
                            </svg>
                        </span>
                        <div>
                            <h3 class="navlink__title"><?php echo $hesklang['customer_my_tickets_heading']; ?></h3>
                            <div class="navlink__descr"><?php echo $hesklang['customer_my_tickets_description']; ?></div>
                        </div>
                    </a>
                    <?php else: ?>
                    <a href="ticket.php" class="navlink">
                        <span class="icon-in-circle" aria-hidden="true">
                            <svg class="icon icon-document">
                                <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-document"></use>
                            </svg>
                        </span>
                        <div>
                            <h3 class="navlink__title"><?php echo $hesklang['view_existing_tickets']; ?></h3>
                            <div class="navlink__descr"><?php echo $hesklang['vet']; ?></div>
                        </div>
                    </a>
                    <?php endif; ?>
                </div>
                <?php if ($hesk_settings['kb_enable']): ?>
                <article class="article">
                    <h2 class="article__heading">
                        <a href="knowledgebase.php">
                            <span class="icon-in-circle" aria-hidden="true">
                                <svg class="icon icon-knowledge">
                                    <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-knowledge"></use>
                                </svg>
                            </span>
                            <span><?php echo $hesklang['kb_text']; ?></span>
                        </a>
                    </h2>
                    <div class="tabbed__head">
                        <ul class="tabbed__head_tabs">
                            <?php
                            if (count($topArticles) > 0):
                            ?>
                            <li class="current" data-link="tab1">
                                <span><?php echo $hesklang['popart']; ?></span>
                            </li>
                            <?php
                            endif;
                            if (count($latestArticles) > 0):
                            ?>
                            <li data-link="tab2">
                                <span><?php echo $hesklang['latart']; ?></span>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="tabbed__tabs">
                        <?php if (count($topArticles) > 0): ?>
                        <div class="tabbed__tabs_tab is-visible" data-tab="tab1">
                            <?php foreach ($topArticles as $article): ?>
                            <a href="knowledgebase.php?article=<?php echo $article['id']; ?>" class="preview">
                                <span class="icon-in-circle" aria-hidden="true">
                                    <svg class="icon icon-knowledge">
                                        <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-knowledge"></use>
                                    </svg>
                                </span>
                                <div class="preview__text">
                                    <h3 class="preview__title"><?php echo $article['subject'] ?></h3>
                                    <p>
                                        <span class="lightgrey"><?php echo $hesklang['kb_cat']; ?>:</span>
                                        <span class="ml-1"><?php echo $article['category']; ?></span>
                                    </p>
                                    <p class="navlink__descr">
                                        <?php echo $article['content_preview']; ?>
                                    </p>
                                </div>
                                <?php if ($hesk_settings['kb_views'] || $hesk_settings['kb_rating']): ?>
                                    <div class="rate">
                                        <?php if ($hesk_settings['kb_views']): ?>
                                            <div style="margin-right: 10px; display: -ms-flexbox; display: flex;">
                                                <svg class="icon icon-eye-close">
                                                    <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-eye-close"></use>
                                                </svg>
                                                <span class="lightgrey"><?php echo $article['views_formatted']; ?></span>
                                            </div>
                                        <?php
                                        endif;
                                        if ($hesk_settings['kb_rating']): ?>
                                            <?php echo hesk3_get_customer_rating($article['rating']); ?>
                                            <?php if ($hesk_settings['kb_views']) echo '<span class="lightgrey">('.$article['votes_formatted'].')</span>'; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </a>
                            <!--[if IE]>
                                <p>&nbsp;</p>
                            <![endif]-->
                            <?php endforeach; ?>
                        </div>
                        <?php
                        endif;
                        if (count($latestArticles) > 0):
                        ?>
                        <div class="tabbed__tabs_tab <?php echo count($topArticles) === 0 ? 'is-visible' : ''; ?>" data-tab="tab2">
                            <?php foreach ($latestArticles as $article): ?>
                                <a href="knowledgebase.php?article=<?php echo $article['id']; ?>" class="preview">
                                    <span class="icon-in-circle" aria-hidden="true">
                                        <svg class="icon icon-knowledge">
                                            <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-knowledge"></use>
                                        </svg>
                                    </span>
                                    <div class="preview__text">
                                        <h3 class="preview__title"><?php echo $article['subject'] ?></h3>
                                        <p>
                                            <span class="lightgrey"><?php echo $hesklang['kb_cat']; ?>:</span>
                                            <span class="ml-1"><?php echo $article['category']; ?></span>
                                        </p>
                                        <p class="navlink__descr">
                                            <?php echo $article['content_preview']; ?>
                                        </p>
                                    </div>
                                    <?php if ($hesk_settings['kb_views'] || $hesk_settings['kb_rating']): ?>
                                        <div class="rate">
                                            <?php if ($hesk_settings['kb_views']): ?>
                                                <div style="margin-right: 10px; display: -ms-flexbox; display: flex;">
                                                    <svg class="icon icon-eye-close">
                                                        <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-eye-close"></use>
                                                    </svg>
                                                    <span class="lightgrey"><?php echo $article['views_formatted']; ?></span>
                                                </div>
                                            <?php
                                            endif;
                                            if ($hesk_settings['kb_rating']): ?>
                                                <?php echo hesk3_get_customer_rating($article['rating']); ?>
                                                <?php if ($hesk_settings['kb_views']) echo '<span class="lightgrey">('.$article['votes_formatted'].')</span>'; ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </a>
                                <!--[if IE]>
                                    <p>&nbsp;</p>
                                <![endif]-->
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="article__footer">
                        <a href="knowledgebase.php" class="btn btn--blue-border" ripple="ripple"><?php echo $hesklang['viewkb']; ?></a>
                    </div>
                </article>
                <?php
                endif;
                if (!$customerLoggedIn && $hesk_settings['alink']):
                ?>
                <div class="article__footer">
                    <a href="<?php echo $hesk_settings['admin_dir']; ?>/" class="link"><?php echo $hesklang['ap']; ?></a>
                </div>
                <?php endif; ?>
            </div>
        </div>
<?php
/*******************************************************************************
The code below handles HESK licensing and must be included in the template.

Removing this code is a direct violation of the HESK End User License Agreement,
will void all support and may result in unexpected behavior.

To purchase a HESK license and support future HESK development please visit:
https://www.hesk.com/buy.php
*******************************************************************************/
$hesk_settings['hesk_license']('Qo8Zm9vdGVyIGNsYXNzPSJmb290ZXIiPg0KICAgIDxwIGNsY
XNzPSJ0ZXh0LWNlbnRlciI+UG93ZXJlZCBieSA8YSBocmVmPSJodHRwczovL3d3dy5oZXNrLmNvbSIgY
2xhc3M9ImxpbmsiPkhlbHAgRGVzayBTb2Z0d2FyZTwvYT4gPHNwYW4gY2xhc3M9ImZvbnQtd2VpZ2h0L
WJvbGQiPkhFU0s8L3NwYW4+PGJyPk1vcmUgSVQgZmlyZXBvd2VyPyBUcnkgPGEgaHJlZj0iaHR0cHM6L
y93d3cuc3lzYWlkLmNvbS8/dXRtX3NvdXJjZT1IZXNrJmFtcDt1dG1fbWVkaXVtPWNwYyZhbXA7dXRtX
2NhbXBhaWduPUhlc2tQcm9kdWN0X1RvX0hQIiBjbGFzcz0ibGluayI+U3lzQWlkPC9hPjwvcD4NCjwvZ
m9vdGVyPg0K',"\104", "a809404e0adf9823405ee0b536e5701fb7d3c969");
/*******************************************************************************
END LICENSE CODE
*******************************************************************************/
?>
    </main>
</div>
<?php include(TEMPLATE_PATH . '../../footer.txt'); ?>
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/jquery-3.5.1.min.js"></script>
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/hesk_functions.js?<?php echo $hesk_settings['hesk_version']; ?>"></script>
<?php outputSearchJavascript(); ?>
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/svg4everybody.min.js"></script>
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/selectize.min.js?<?php echo $hesk_settings['hesk_version']; ?>"></script>
<script src="<?php echo TEMPLATE_PATH; ?>customer/js/app<?php echo $hesk_settings['debug_mode'] ? '' : '.min'; ?>.js?<?php echo $hesk_settings['hesk_version']; ?>"></script>
</body>

</html>
