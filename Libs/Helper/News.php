<?php
namespace GDelivery\Libs\Helper;

use Abstraction\Object\Message;
use Abstraction\Object\Result;
use GDelivery\Libs\Config;

class News {

    private static function convertToStdClass($news, $options = []) {
        $temp = new \stdClass();

        $temp->id = $news->ID;
        $temp->title = $news->post_title;
        $temp->subDescription = $news->subDescription ?? '';
        $temp->description = $news->post_content;
        $temp->thumbnail = $news->thumbnail ?? '';
        $temp->createdAt = $news->post_date;

        return $temp;
    }

    public static function getAll($params) {
        $res = new Result();

        $query = new \WP_Query(
            [
                'post_type' => 'post',
                'post_status'=>'publish',
                'paged'=> $params['page'],
                'posts_per_page'=> $params['perPage'],
            ]
        );

        $news = $query->posts;
        foreach ($news as &$value) {
            $value = self::convertToStdClass($value);
        }
        unset($value);

        $res->messageCode = Message::SUCCESS;
        $res->message = 'Thành công';
        $res->result = $news;

        return $res;
    }

    public static function newsDetail($id) {
        $res = new Result();

        $news = self::convertToStdClass(get_post($id));

        $res->messageCode = Message::SUCCESS;
        $res->message = 'Thành công';
        $res->result = $news;

        return $res;
    }

    public static function topOne() {
        $res = new Result();

        $query = new \WP_Query(
            [
                'post_type' => 'post',
                'post_status'=>'publish',
                'paged'=> 1,
                'posts_per_page'=> 1,
            ]
        );

        $news = $query->posts[0];
        $topOneNews =  self::convertToStdClass($news);

        $res->messageCode = Message::SUCCESS;
        $res->message = 'Thành công';
        $res->result = $topOneNews;

        return $res;
    }

    public static function topList($params = []) {
        $res = new Result();

        $query = new \WP_Query(
            [
                'post_type' => 'post',
                'post_status'=>'publish',
                'paged'=> $params['page'] ?? 1,
                'posts_per_page'=> $paramsp['perPage'] ?? 8,
            ]
        );

        $news = $query->posts;
        $topListNews = [];
        foreach ($news as &$value) {
            $topListNews[] = self::convertToStdClass($value);
            break;
        }

        $res->messageCode = Message::SUCCESS;
        $res->message = 'Thành công';
        $res->result = $topListNews;

        return $res;
    }
} // end class
