<!-- Tabbed bar -->
<ul class="tab"> 
    <?php
    foreach($file_names as $key => $file_name) {
        $tab = "<li";
        if($active_index == $key) {
            $tab .= ' class="active"';
        }
        $tab .= ' value="' . $key . '"><a href="#"><span>' . $file_name . "</span></a></li>";
        echo $tab;
    }
    ?>
</ul>
