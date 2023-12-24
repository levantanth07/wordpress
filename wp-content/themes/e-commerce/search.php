<?php get_header(); ?>

<section class="main">
    <div class="topbanner" style="background-image:url('<?php bloginfo('template_url'); ?>/img/6079backtop.jpg')"></div>
    <div class="container">
        <div class="module list-news product-detail cat-detail">
            <div class="row">
<div class="col-xs-12 col-sm-12 col-md-9 col-md-push-3 col-right detail-left">
    <div class="nav_bar"><a href="/"><i class="fa fa-home"></i>Trang chủ</a><a href="#"><?php the_search_query(); ?></a> </div>
    <div class="top-cat">
        <div class="cat-title">
            <h1><?php single_tag_title(); ?></h1>
        </div>
    </div>
    <div class="row news-list">
<?php
while ( $wp_query->have_posts() ) : $wp_query->the_post();
?>
        <div class="col-xs-12 news-item">
            <div class="image-thumb"><a class="flash" href="<?php the_permalink();?>">
			<img class="img-responsive" src="<?php thumb();?>" alt="<?php the_title(); ?>"></a>               
			</div>
            <div class="news-col">
                <h3><a href="<?php the_permalink();?>"><?php the_title(); ?></a></h3>
				<span class="info_news"><?php the_time( 'd/m/Y') ?></span>
                <div class="news-description"><?php tj_content_limit(120); ?>
					<a href="<?php the_permalink();?>">Xem chi tiết</a> 
				</div>
			</div>
		</div>
<?php  
endwhile;
?>
		<div id="phantrang" class="phantrang_news" style="text-align: right; padding: 10px 0; width: 100%;">
			 <?php if(function_exists('wp_pagenavi')) { wp_pagenavi(); } else {page_navi();}?>
		</div>
	</div>
</div>
				<div class="col-xs-12 col-sm-12 col-md-3 col-md-pull-9 col-left">
					<?php include('slidebar.php');?>
					<div class="_box hidden-xs hidden-sm">
						<div class="banner"></div>
					</div>
				</div>
			</div>
		</div>
    </div>
</section>

<?php  get_footer();?>