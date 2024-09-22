<?php
namespace H5VP\Rest;
use H5VP\Model\Video;

class VideoController extends \WP_REST_Controller{
    protected $namespace = 'h5vp';
    protected $version = 'v1';
    protected $route = 'video';
    function __construct(){
        // $this->route = '/single(?:/(?P<id>\d+))?';
    }

    function run(){
        add_action('rest_api_init', [$this, 'register_route']);
    }

    public function register_route(){
        register_rest_route( $this->namespace.'/'.$this->version,
        $this->route,
         [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_items'],
                'permission_callback' => [$this, 'permision_callback'],
                'args' => []
            ],
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_item'],
                'permission_callback' => [$this, 'permision_callback'],
                'args' => []
            ]
         ] );

        register_rest_route( $this->namespace.'/'.$this->version,
        $this->route.'/(?P<id>\d+)',
         [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_item'],
                'permission_callback' => [$this, 'permision_callback'],
                'args' => []
            ],
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_item'],
                'permission_callback' => [$this, 'permision_callback'],
                'args' => []
            ]
         ] );
    }

    function create_item($request){
        global $wpdb;
        $table_name = $wpdb->prefix.'h5vp_videos';
        $args = $this->prepare($request);
        
        if(!user_can($args['user_id'], 'edit_posts')){
            return new \WP_Error( 'rest_forbidden', esc_html__( 'Request Forbidden', 'h5vp' ), array( 'status' => 401 ) );
        }

        $video = new Video();
        $insert = $video->create($args);

        if($insert){
            return new \WP_REST_Response([
                'status' => 201,
                'response' => 'Video Successfully Created',
                'insert' => $insert
            ]);
        }else {
            return new \WP_REST_Response([
                'status' => 500,
                'response' => 'server error',
                'info' => []
            ]);
        }
    }

    public function get_item( $request){
        global $wpdb;
        $params = $request->get_params();
        $id = sanitize_text_field($params['id'] ?? false);
        $table_name = $wpdb->prefix.'h5vp_videos';
        if(!$id){
            return new \WP_Error( 'rest_forbidden', esc_html__( 'OMG you can not view private data.', 'h5vp' ), array( 'status' => 401 ) );
        }
        $video = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id=%s", $id));
        if(!$video){
            return new \WP_Error( 'not_found', esc_html__( 'Data not found', 'h5vp' ), array( 'status' => 404 ) );
        }
        return new \WP_REST_Response($video);
    }

    /**
     * get all items
     */
    public function get_items( $request){
        global $wpdb;
        $table_name = $wpdb->prefix.'h5vp_videos';
        $video = $wpdb->get_results("SELECT * FROM $table_name");
        if(count($video)< 1){
            return new \WP_Error( 'not_found', esc_html__( 'Data not found', 'h5vp' ), array( 'status' => 404 ) );
        }
        return new \WP_REST_Response($video);
    }

    /**
     * update video data
     */
    public function update_item($request){
        $info = $this->prepare_for_update($request);
        $args = $info;
        unset($args['id']);
        $video = new Video();
        $update = $video->update($args, [
            'id' => $info['id']
        ]);

        if($update){
            return new \WP_REST_Response(['status' => 200, 'response' => 'Data updated']);
        }
        return new \WP_REST_Response(['status' => 200, 'response' => 'Data didn\'t updated']);
    }

    public static function meta($id, $key, $default = null){
        if (metadata_exists('post', $id, $key)) {
            $value = get_post_meta($id, $key, true);
            if ($value != '') {
                return $value;
            } else {
                return $default;
            }
        } else {
            return $default;
        }
    }

    /**
     * prepare data for database
     */
    public function prepare($request){
        // return ['nothing' => 'true'];
        $request =  $request->get_params();

        return [
            'title' => isset($request['title']) ? sanitize_text_field($request['title']) : '',
            'type' => isset($request['type']) ? sanitize_text_field($request['type']) : '',
            'src' => isset($request['src']) ? sanitize_text_field($request['src']) :'',
            'user_id' => isset($request['user_id']) ? sanitize_text_field($request['user_id']) :'',
        ];
    }

    public function prepare_for_update($request){
        // return ['nothing' => 'true'];
        $request =  $request->get_params();

        $args = [];
        foreach($request as $key => $value){
            $args[$key] = sanitize_text_field( $value );
        }
        return $args;
    }

    public function get_user_id(){
        return get_current_user_id();
    }

    public function permision_callback($request){
        return $this->another_check($request);
    }

    public function another_check($request){
        return true;
        if ( ! current_user_can( 'edit_posts' ) ) {
            return new \WP_Error( 'rest_forbidden', esc_html__( 'OMG you can not view private data.', 'my-text-domain' ), array( 'status' => 401 ) );
        }
     
        // This is a black-listing approach. You could alternatively do this via white-listing, by returning false here and changing the permissions check.
        return true;
    }

    public function getTitle($data){
        if($data['type'] == 'youtube'){
            if(strlen($data['src']) < 13){
                $data['src'] = 'https://www.youtube.com/watch?v='.$data['src'];
            }
            $videoid = '';
            if(preg_match("/watch\?v=(\w+)/i", $data['src'], $match)){
                $videoid = $match[1];
                $apikey = 'AIzaSyA6pMW1ZJBii9VewZj_cWZPjMTdfmKyVKE';
                $json = file_get_contents('https://www.googleapis.com/youtube/v3/videos?id=' . $videoid . '&key=' . $apikey . '&part=snippet');
                $info = json_decode($json, true);
                $data['title'] = $info['items'][0]['snippet']['title'] ?? $data['title'];
                $data['external_id'] = $videoid;
            }
        }
        if($data['type'] == 'vimeo'){
            if(strlen($data['src']) < 13){
                $data['src'] = 'https://vimeo.com/'.$data['src'];
            }
            $videoid = '';
            // http://vimeo.com/api/v2/video/50961789.json
            if(preg_match("/vimeo.com\/(\w+)/i", $data['src'], $match)){
                $videoid = $match[1];
                $json = file_get_contents('http://vimeo.com/api/v2/video/'.$videoid.'.json');
                $info = json_decode($json, true);
                $data['title'] = $info[0]['title'] ?? $data['title'];
                $data['external_id'] = $videoid;
            }
        }

        return $data;
    }

}

$video = new VideoController();
$video->run();

