<?php
global $hesk_settings, $hesklang;
/**
 * @var array $article
 * @var array $attachments
 * @var boolean $showRating
 * @var string $categoryLink
 * @var array $relatedArticles
 * @var bool $customerLoggedIn - `true` if a customer is logged in, `false` otherwise
 * @var array $customerUserContext - User info for a customer if logged in.  `null` if a customer is not logged in.
 */

// This guard is used to ensure that users can't hit this outside of actual HESK code
if (!defined('IN_SCRIPT')) {
    die();
}

require_once(TEMPLATE_PATH . 'customer/util/alerts.php');
require_once(TEMPLATE_PATH . 'customer/util/rating.php');
require_once(TEMPLATE_PATH . 'customer/util/kb-search.php');
require_once(TEMPLATE_PATH . 'customer/partial/login-navbar-elements.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title><?php echo $hesk_settings['tmp_title']; ?></title>
    <meta http-equiv="X-UA-Compatible" content="IE=Edge" />
    <meta name="viewport" content="width=device-width,minimum-scale=1.0,maximum-scale=1.0" />
    <?php include(HESK_PATH . 'inc/favicon.inc.php'); ?>
    <meta name="format-detection" content="telephone=no" />
    <link rel="stylesheet" media="all" href="<?php echo TEMPLATE_PATH; ?>customer/css/app<?php echo $hesk_settings['debug_mode'] ? '' : '.min'; ?>.css?<?php echo $hesk_settings['hesk_version']; ?>" />
    <link rel="stylesheet" media="all" href="<?php echo TEMPLATE_PATH; ?>customer/css/prism.css" />
    <script src="<?php echo TEMPLATE_PATH; ?>customer/js/prism.js"></script>
    <!--[if IE]>
    <link rel="stylesheet" media="all" href="<?php echo TEMPLATE_PATH; ?>customer/css/ie9.css" />
    <![endif]-->
    <?php include(TEMPLATE_PATH . '../../head.txt'); ?>
    <style>
        <?php outputSearchStyling(); ?>
    </style>
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
                    <a href="<?php echo $hesk_settings['hesk_url']; ?>">
                        <span><?php echo $hesk_settings['hesk_title']; ?></span>
                    </a>
                    <svg class="icon icon-chevron-right">
                        <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-chevron-right"></use>
                    </svg>
                    <?php foreach ($hesk_settings['public_kb_categories'][$article['catid']]['parents'] as $parent_id): ?>
                    <a href="knowledgebase.php<?php if ($parent_id > 1) echo "?category={$parent_id}"; ?>">
                        <span><?php echo $hesk_settings['public_kb_categories'][$parent_id]['name']; ?></span>
                    </a>
                    <svg class="icon icon-chevron-right">
                        <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-chevron-right"></use>
                    </svg>
                    <?php endforeach; ?>
                    <a href="knowledgebase.php<?php if ($article['catid'] > 1) echo "?category={$article['catid']}"; ?>">
                        <span><?php echo $hesk_settings['public_kb_categories'][$article['catid']]['name']; ?></span>
                    </a>
                    <svg class="icon icon-chevron-right">
                        <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-chevron-right"></use>
                    </svg>
                    <div class="last"><?php echo $article['subject']; ?></div>
                </div>
            </div>
        </div>
        <div class="main__content">
            <div class="contr">
                <div class="help-search">
                    <?php displayKbSearch(); ?>
                </div>
                <?php hesk3_show_messages($serviceMessages); ?>
                <div class="ticket ticket--article">
                    <div class="ticket__body">
                        <article class="ticket__body_block naked">
                            <h1><?php echo $article['subject']; ?></h1>
                            <div class="block--description browser-default">
                                <?php echo $article['content']; ?>
                            </div>
                            <?php if (count($attachments)): ?>
                            <div class="block--uploads">
                                <?php foreach ($attachments as $attachment): ?>
                                &raquo;
                                <svg class="icon icon-attach">
                                    <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-attach"></use>
                                </svg>
                                <a title="<?php echo $hesklang['dnl']; ?>" href="download_attachment.php?kb_att=<?php echo $attachment['id']; ?>" rel="nofollow">
                                    <?php echo $attachment['name']; ?>
                                </a>
                                <br>
                                <?php
                                endforeach;
                                ?>
                            </div>
                            <?php
                            endif;
                            if ($showRating):
                            ?>
                            <div id="rate-me" class="ticket__block-footer">
                                <span><?php echo $hesklang['rart']; ?></span>
                                <a href="javascript:" onclick="HESK_FUNCTIONS.rate('rate_kb.php?rating=5&amp;id=<?php echo $article['id']; ?>','article-rating');document.getElementById('rate-me').innerHTML='<?php echo hesk_slashJS($hesklang['tyr']); ?>';" class="link" rel="nofollow">
                                    <?php echo $hesklang['yes_title_case']; ?>
                                </a>
                                <span>|</span>
                                <a href="javascript:" onclick="HESK_FUNCTIONS.rate('rate_kb.php?rating=1&amp;id=<?php echo $article['id']; ?>','article-rating');document.getElementById('rate-me').innerHTML='<?php echo hesk_slashJS($hesklang['tyr']); ?>';" class="link" rel="nofollow">
                                    <?php echo $hesklang['no_title_case']; ?>
                                </a>
                            </div>
                            <?php endif; ?>
                        </article>
                    </div>
                    <div class="ticket__params">
                        <section class="params--block details">
                            <h2 class="accordion-title">
                                <span><?php echo $hesklang['ad']; ?></span>
                            </h2>
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="title"><?php echo $hesklang['aid']; ?>:</div>
                                    <div class="value"><?php echo $article['id']; ?></div>
                                </div>
                                <div class="row">
                                    <div class="title"><?php echo $hesklang['category']; ?>:</div>
                                    <div class="value">
                                        <a href="<?php echo $categoryLink; ?>" class="link">
                                            <?php echo $article['cat_name']; ?>
                                        </a>
                                    </div>
                                </div>
                                <?php if ($hesk_settings['kb_date']): ?>
                                    <div class="row">
                                        <div class="title"><?php echo $hesklang['dta']; ?>:</div>
                                        <div class="value"><?php echo hesk_date($article['dt'], true); ?></div>
                                    </div>
                                <?php
                                endif;
                                if ($hesk_settings['kb_views']): ?>
                                <div class="row">
                                    <div class="title">
                                        <?php echo $hesklang['views']; ?>:
                                    </div>
                                    <div class="value">
                                        <?php echo $article['views_formatted']; ?>
                                    </div>
                                </div>
                                <?php
                                endif;
                                if ($hesk_settings['kb_rating']):
                                ?>
                                <div class="row">
                                    <div class="title">
                                        <?php echo $hesklang['rating']; ?>
                                        <?php if ($hesk_settings['kb_views']) echo ' ('.$hesklang['votes'].')'; ?>:
                                    </div>
                                    <div class="value">
                                        <div id="article-rating" class="rate">
                                            <?php echo hesk3_get_customer_rating($article['rating']); ?>
                                            <?php if ($hesk_settings['kb_views']) echo ' <span class="lightgrey">('.$article['votes_formatted'].')</span>'; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <div style="text-align:right">
                                    <a href="javascript:history.go(<?php echo isset($_GET['rated']) ? '-2' : '-1'; ?>)" class="link">
                                        <svg class="icon icon-back go-back">
                                            <use xlink:href="<?php echo TEMPLATE_PATH; ?>customer/img/sprite.svg#icon-back"></use>
                                        </svg>
                                        <?php echo $hesklang['back']; ?>
                                    </a>
                                </div>
                            </div>
                        </section>
                        <?php if (count($relatedArticles) > 0): ?>
                        <section class="params--block">
                            <h2 class="accordion-title">
                                <span><?php echo $hesklang['relart']; ?></span>
                            </h2>
                            <div class="accordion-body">
                                <ul class="list">
                                    <?php foreach ($relatedArticles as $id => $subject): ?>
                                    <li>
                                        <a href="knowledgebase.php?article=<?php echo $id; ?>">
                                            <?php echo $subject; ?>
                                        </a>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </section>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="divider"></div>
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
