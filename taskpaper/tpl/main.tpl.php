<!--
The main view template
-->
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" 
"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
    <title>
        <?php echo $title; ?>
    </title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
    <link rel="stylesheet" type="text/css" href="css/style.css" media="screen">
    <link rel="stylesheet" type="text/css" href="css/tabs.css" media="screen">
    <link rel="stylesheet" type="text/css" href="css/print.css" media="print">
    <script type="text/javascript" src="./lib/jquery.js"></script>
    <script type="text/javascript" src="./scripts/ajax_events.js"></script>
</head>
<body>
    <div id="frame">
        <?php include('tpl/banner.tpl.php'); ?>
        <?php echo $all_errors; ?>
        <div id="header">
            <div class="left">
                <div id="logo">
                    <a href="" title="Back to start page">
                        <img src="icons/logo.png">
                    </a>
                </div>
                <input type="image" src="icons/home.png" class="icon" id="back-button" value="Back" title="Back to the start page (index)">
                <a href="help.html"><img src="icons/help.png" class="icon" title="Taskpaper Help"></a>
            </div>
            <div class="right">
                <input type="text" id="find-box" accesskey="c" title="Type words, tags, or dates to search for; or enter a new task"/>
                <input type="image" src="icons/search.png" class="icon" id="find-button" title="Search the Taskpaper or Add a new task"/>
            </div>
        </div>
        <div id="tab-bar">
            <?php echo $filetabs_view; ?>
        </div>
        <div class="colmask threecol">
            <div class="colmid">
		<div class="colleft">
                    <div class="tasks">
                        <div id="view-tasks">
                            <?php echo $task_view; ?>
                        </div>
                        <div id="edit-tasks">
                            <div class="find-replace-bar">
                                <input type="image" src="icons/save.png" class="icon save-button" value="Save" title="Save your changes">
                                <label>Find: <input type="text" id="find-word"></label>
                                <label> Replace with: <input type="text" id="replace-word"></label>
                                <input type="button" value="Go!" id="replace-button">
                            </div>
                            <textarea></textarea><br/>
                            <input type="image" src="icons/save.png" class="icon save-button" value="Save" title="Save your changes">
                            <?php include('tpl/cheatsheet.tpl.php'); ?>
                        </div>
                    </div>
                    <div class="projects">
                        <?php echo $project_view; ?>
                    </div>
                    <div class="tags">
                        <?php echo $tag_view; ?>
                    </div>
                    <input type="hidden" id="current-state" value="<?php echo $start_page; ?>">
                    <input type="hidden" id="ajax-file" value="<?php echo $ajax_file; ?>">
		</div>
            </div>
        </div>
        <div id="footer"><a href="readme.txt">About</a></div>
    </div>
	</body>
</html>