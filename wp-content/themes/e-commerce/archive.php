<?php get_header(); ?>
<?php 
$os = array("duan_tiendo", "duan_time", "duan_vitri");
if (in_array(get_queried_object()->taxonomy, $os)) {
   include('taxonomy-duan_cat.php');
   die();
}
?>
<div class="banner-detail">
        <img class="banner-detail__img" src="<?php bloginfo('template_url'); ?>/data/bg-tintuc.jpg" />
        <img class="banner-detail__img-overlay" src="<?php bloginfo('template_url'); ?>/data/OVerlay_bk_-_title.png"/>
        <div class="container banner-detail__container">
            <div class="banner-detail__text">
                <?php single_cat_title();?>
                <div class="banner-detail__des"></div>
            </div>
        </div>
</div>


<div class="news background-gray">
    <div class="container">
        <div class="breadcrumb__wrap">
            <a href="/" class="breadcrumb__item">Trang chủ</a>
            <a class="breadcrumb__item active"><?php single_cat_title();?></a>
        </div>
        <div class="news__list row">
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>	
            <div class="news__item col-xs-12 col-md-6">
                <div class="news__wrap">
                    <div class="news__image">
                        <a href="<?php the_permalink();?>" title="<?php the_title();?>">
                            <img src="<?php thumb();?>" alt="<?php the_title();?>">
                        </a>
                    </div>
                    <div class="news__date">
                        <?php the_time('d/m/Y');?>
                    </div>
                    <div class="news__title">
                        <a href="<?php the_permalink();?>" title="<?php the_title();?>"><?php the_title();?></a>
                    </div>
                    <div class="news__des"><?php tj_content_limit(220); ?></div>
                    <a href="<?php the_permalink();?>" title="<?php the_title();?>" class="news__link">Xem chi tiết...</a>
                </div>
            </div>

<?php endwhile; else:  endif; ?>
        </div>
        <nav aria-label="Page navigation">
        <?php if(function_exists('wp_pagenavi')) { wp_pagenavi(); } else {page_navi();}?>
		</nav>

    </div>
</div>
<?php  get_footer();?>