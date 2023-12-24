<?php get_header('order'); ?>
<?php
if (have_posts()) :
    while (have_posts()) :
        the_post(); ?>

        <!-- content list -->
        <div class="wrap-list">
            <div class="container">
                <div class="row">
                    <div class="col-xl-12 col-md-12">
                        <?=get_the_content()?>
                    </div>
                </div>
            </div>
        </div>
        <!-- end list -->

<?php
    endwhile;
endif; ?>
<?php get_footer();?>

