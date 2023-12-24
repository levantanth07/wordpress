<?php
namespace GDelivery\Libs\Helper;

use Abstraction\Object\Message;
use Abstraction\Object\Result;
use GDelivery\Libs\Config;

class Recipes {

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
                'post_type' => 'recipes',
                'post_status' => 'publish',
                'paged' => isset($params['page']) ? (int) $params['page'] : 1,
                'posts_per_page' => $params['perPage'] ?? 8,
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

    public static function detail($id) {
        $res = new Result();

        $query = new \WP_Query(
            [
                'post_type' => 'recipes',
                'p' => $id,
                'post_status' => 'publish',
            ]
        );

        $recipes = self::convertToStdClass($query->posts[0]);

        $res->messageCode = Message::SUCCESS;
        $res->message = 'Thành công';
        $res->result = $recipes;

        return $res;
    }
} // end class
