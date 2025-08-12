<?php

namespace H5VP\Base;


use H5VP\Model\View as View;
use H5VP\Model\Video as Video;

class Analytics
{
    public function register()
    {
        if (!is_admin()) {
            return;
        }
        $settings = get_option('h5vp_option');
        if (h5vp_fs()->can_use_premium_code() && (!isset($settings['enable_analytics']) || (isset($settings['enable_analytics']) && $settings['enable_analytics']  != '0'))) {
            add_action('admin_menu', [$this, 'admin_menu']);
        }
    }

    public function admin_menu()
    {
        add_submenu_page('html5-video-player', 'Analytics', 'Analytics', 'manage_options', 'analytics', [$this, 'analytics_callback'], 50);
    }

    public function analytics_callback()
    {
        if (isset($_GET['user'])) {
            $id = sanitize_text_field($_GET['user']);
            $this->analyticsUser($id);
        } else if (isset($_GET['video'])) {
            $id = sanitize_text_field($_GET['video']);
            $this->analyticsVideo($id);
        } else {
            $this->analyticsMain();
        }
    }

    public function analyticsMain()
    {
        $view = new View();
        $video = new Video();

        $video_ids = $video->get_all_video_id();
        $views = $view->get_views();

        $users = [];
        $most_viewed_videos = [];
        $statistics[0] = 0;
        foreach ($views as $key => $value) {
            if (!in_array($value->video_id, $video_ids)) {
                continue;
            }
            //for top user 
            if ($value->user_id != 0) {
                if (array_key_exists($value->user_id, $users)) {
                    $users[$value->user_id]['views'] += 1;
                    $users[$value->user_id]['duration'] += $value->duration;
                } else {
                    $user = get_user_by('id', $value->user_id);
                    $users[$value->user_id]['views'] = 1;
                    $users[$value->user_id]['duration'] = $value->duration;
                    $users[$value->user_id]['name'] = $user->data->display_name;
                }
            }

            //for top videos
            if (array_key_exists($value->video_id, $most_viewed_videos)) {
                $most_viewed_videos[$value->video_id]['views'] += 1;
                $most_viewed_videos[$value->video_id]['total_duration'] += $value->duration;
            } else {
                $most_viewed_videos[$value->video_id]['views'] = 1;
                $most_viewed_videos[$value->video_id]['total_duration'] = $value->duration;
                $most_viewed_videos[$value->video_id]['video_id'] = $value->video_id;
            }

            $explode = explode(' ', $value->created_at);
            if (array_key_exists($explode[0], $statistics)) {
                $statistics[$explode[0]] += 1;
            } else {
                $statistics[$explode[0]] = 1;
            }
        }

        arsort($most_viewed_videos);
        $topVideos = $video->get_top_videos($this->arrayToString(array_keys($most_viewed_videos)));

        $dates = [];
        foreach (array_keys($statistics) as $data) {
            $dates[] = wp_date('F j', strtotime($data));
        }

?>
        <div class="h5vp-analytics">
            <div class="chart-user">
                <div class="chart">
                    <canvas id="myChart" data-labels='<?php echo esc_attr(wp_json_encode($dates)) ?>' data-data="<?php echo esc_attr(wp_json_encode(array_values($statistics))); ?>"></canvas>
                </div>
                <div class="user">
                    <h3><?php esc_html_e('Top Users', 'h5vp') ?></h3>
                    <table>
                        <thead>
                            <tr>
                                <th width="110px"><?php esc_html_e("User", 'h5vp') ?></th>
                                <th width="100px"><?php esc_html_e('Total View', 'h5vp') ?></th>
                                <th><?php esc_html_e('Avg Watch Time', 'h5vp') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($users as $key => $value): ?>
                                <tr to="user=<?php echo esc_attr($key); ?>">
                                    <td><a href="<?php echo esc_url(admin_url('/admin.php?page=analytics&user=' . $value['name'])) ?>"><?php echo esc_html($value['name']) ?></a></td>
                                    <td><?php echo esc_html($value['views']) ?></td>
                                    <td><?php echo esc_html($this->secToHR($value['duration'] / $value['views']));  ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <tbody>
                    </table>
                </div>
            </div>
            <div class="top-videos">
                <h3><?php esc_html_e('Top Videos', 'h5vp'); ?></h3>
                <table>
                    <thead>
                        <tr>
                            <th><?php esc_html_e("Video", 'h5vp') ?></th>
                            <th><?php esc_html_e('Total View', 'h5vp') ?></th>
                            <th><?php esc_html_e('Avg Watch Time', 'h5vp') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($most_viewed_videos as $key => $value):
                        ?>
                            <tr to="video=<?php echo esc_attr($key); ?>">
                                <td><a href="<?php echo esc_url(admin_url('/admin.php?page=analytics&video=' . $value['video_id'])) ?>"><?php echo esc_html($this->getVideoTitle($topVideos, $key)) ?></a></td>
                                <td><?php echo esc_html($value['views']);
                                    echo $value['views'] > 1 ? ' views' : ' view'; ?></td>
                                <td><?php echo esc_html($this->secToHR($value['total_duration'] / $value['views'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php
    }

    /**
     * Video Page
     */
    public function analyticsVideo($id)
    {
        $view = new View();
        $views = $view->get_video_views($id);
        $users = [];
        $videos = [];
        $statistics[0] = 0;
        foreach ($views as $key => $value) {
            //for top user 
            if ($value->user_id != 0) {
                if (array_key_exists($value->user_id, $users)) {
                    $users[$value->user_id]['views'] += 1;
                    $users[$value->user_id]['duration'] += $value->duration;
                } else {
                    $user = get_user_by('id', $value->user_id);
                    $users[$value->user_id]['views'] = 1;
                    $users[$value->user_id]['duration'] = $value->duration;
                    $users[$value->user_id]['name'] = $user->data->display_name;
                }
            }

            //for top videos
            if (array_key_exists($value->video_id, $videos)) {
                $videos[$value->video_id]['views'] += 1;
                $videos[$value->video_id]['duration'] += $value->duration;
            } else {
                $videos[$value->video_id]['views'] = 1;
                $videos[$value->video_id]['duration'] = $value->duration;
            }

            $explode = explode(' ', $value->created_at);
            if (array_key_exists($explode[0], $statistics)) {
                $statistics[$explode[0]] += 1;
            } else {
                $statistics[$explode[0]] = 1;
            }
        }

        $video = new Video();
        arsort($videos);
        $topVideos = $video->get_top_videos($this->arrayToString(array_keys($videos)));
        $dates = [];
        foreach (array_keys($statistics) as $data) {
            $dates[] = wp_date('F j', strtotime($data));
        }
    ?>
        <div class="h5vp-analytics">
            <a class="back_to_dashboard" href="<?php echo esc_url(admin_url()) ?>/admin.php?page=analytics"><span class="dashicons dashicons-arrow-left-alt"></span>back to dashboard</a>
            <div class="chart">
                <div class="chart">
                    <canvas id="myChart" data-labels='<?php echo esc_attr(wp_json_encode($dates)) ?>' data-data="<?php echo esc_attr(wp_json_encode(array_values($statistics))); ?>"></canvas>
                </div>
            </div>
        </div>
    <?php
    }

    /**
     * User Page
     */
    public function analyticsUser($id)
    {
        $view = new View();
        $video = new Video();
        $views = $view->user($id);
        $video_ids = $video->get_all_video_id();
        $users = [];
        $videos = [];
        $totalDuration = 0;
        foreach ($views as $key => $value) {
            if (!in_array($value->video_id, $video_ids)) {
                continue;
            }
            $totalDuration += $value->duration;
            //for top videos
            if (array_key_exists($value->video_id, $videos)) {
                $videos[$value->video_id]['views'] += 1;
                $videos[$value->video_id]['duration'] += $value->duration;
            } else {
                $videos[$value->video_id]['views'] = 1;
                $videos[$value->video_id]['duration'] = $value->duration;
            }
        }

        $video = new Video();
        arsort($videos);

        $topVideos = $video->get_top_videos($this->arrayToString(array_keys($videos)));

    ?>
        <div class="h5vp-analytics">
            <a class="back_to_dashboard" href="<?php echo esc_url(site_url()) ?>/wp-admin/edit.php?post_type=videoplayer&page=analytics"><span class="dashicons dashicons-arrow-left-alt"></span>back to dashboard</a>
            <div class="chart-user">
                <div class="user-boxes">
                    <div class="user-box">
                        <p>Total View</p>
                        <h2><?php echo esc_html(count($views)); ?></h2>
                    </div>
                    <div class="user-box">
                        <p>Average watch time</p>
                        <h2><?php echo esc_html($this->secToHR($totalDuration / count($views))); ?></h2>
                    </div>
                    <div class="user-box">
                        <p>Total watch time</p>
                        <h2><?php echo esc_html($this->secToHR($totalDuration)); ?></h2>
                    </div>
                </div>
            </div>

            <div class="top-videos">
                <h3><?php esc_html_e('Top Videos', 'h5vp'); ?></h3>
                <table>
                    <thead>
                        <tr>
                            <th><?php esc_html_e("Video", 'h5vp') ?></th>
                            <th><?php esc_html_e('Total View', 'h5vp') ?></th>
                            <th><?php esc_html_e('AVG Watch Time', 'h5vp') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($videos as $key => $value): ?>
                            <tr to="video=<?php echo esc_attr($key); ?>">
                                <td><?php echo esc_html($this->getVideoTitle($topVideos, $key)) ?></td>
                                <td><?php echo esc_html($value['views']);
                                    echo $value['views'] > 1 ? ' views' : ' view'; ?></td>
                                <td><?php echo esc_html($this->secToHR($value['duration'] / $value['views'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
<?php
    }

    public function arrayToString($array = [])
    {
        $implode = implode(',', $array);
        return $implode;
    }

    /**
     * format second
     */
    public function secToHR($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = (int) ($seconds / 60);
        $seconds = (int) $seconds % 60;
        $hLabel = $hours > 1 ? 'hours' : 'hour';
        $mLabel = $minutes > 1 ? 'minutes' : 'minute';
        $sLabel = $seconds > 1 ? 'seconds' : 'second';
        return $hours > 0 ? "$hours $hLabel $minutes $mLabel " : ($minutes > 0 ? "$minutes $mLabel $seconds $sLabel" : "$seconds $sLabel");
    }


    public function getVideoTitle($videos, $videoId)
    {
        foreach ($videos as $video) {
            if ($video->id == $videoId) {
                return $video->title !== '' ? $video->title : $video->src;
            }
        }
    }
}
