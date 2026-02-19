<!--/******\ <coding-tips.com> 2018 \******/-->
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Paginated responsive image gallery using PHP, MySQL and CSS</title>
        <link href="css/css.css" rel="stylesheet" type="text/css" />
        <meta name="theme-color" content="#444" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>

    <body>

        <div id="main">

            <header>
                <div class="margin">
                    <div class="logo-outer">
                        <a href="https://coding-tips.com">
                            <img src="images/logo.png.png" class="logo"/>
                        </a>
                    </div>
                </div>
            </header>

            <div class="margin">

                <h1>
                    Paginated responsive image gallery using PHP, MySQL and CSS
                </h1>

                <a href="https://coding-tips.com/php/paginated-responsive-image-gallery/">
                    Go to the tutorial
                </a>
                <div id="pagination"><!-- #pagination start -->

                    <?php
                    include_once('dbcon/dbcon.php'); //Include the database connection
//////FIRST WE SET UP THE TOTAL images PER PAGE & CALCULATIONS:
                    $per_page = 12; // Number of images per page, change for a different number of images per page
// Get the page and offset value:
                    if (isset($_GET['page'])) {
                        $page = $_GET['page'] - 1;
                        $offset = $page * $per_page;
                    } else {
                        $page = 0;
                        $offset = 0;
                    }

// Count the total number of images in the table ordering by their id's ascending:
                    $images = "SELECT count(id) FROM images ORDER by id ASC";
                    $result = mysqli_query($dbCon, $images);
                    $row = mysqli_fetch_array($result);
                    $total_images = $row[0];

// Calculate the number of pages:
                    if ($total_images > $per_page) {//If there is more than one page
                        $pages_total = ceil($total_images / $per_page);
                        $page_up = $page + 2;
                        $page_down = $page;
                        $display = ''; //leave the display variable empty so it doesn't hide anything
                    } else {//Else if there is only one page
                        $pages = 1;
                        $pages_total = 1;
                        $display = ' class="display-none"'; //class to hide page count and buttons if only one page
                    }

////// THEN WE DISPLAY THE PAGE COUNT AND BUTTONS:

                    echo '<h2' . $display . '>Page ';
                    echo $page + 1 . ' of ' . $pages_total . '</h2>'; //Page out of total pages

                    $i = 1; //Set the $i counting variable to 1

                    echo '<div id="pageNav"' . $display . '>'; //our $display variable will do nothing if more than one page
// Show the page buttons:
                    if ($page) {
                        echo '<a href="index.php"><button><<</button></a>'; //Button for first page [<<]
                        echo '<a href="index.php?page=' . $page_down . '"><button><</button></a>'; //Button for previous page [<]
                    }

                    for ($i = 1; $i <= $pages_total; $i++) {
                        if (($i == $page + 1)) {
                            echo '<a href="index.php?page=' . $i . '"><button class="active">' . $i . '</button></a>'; //Button for active page, underlined using 'active' class
                        }

//In this next if statement, calculate how many buttons you'd like to show. You can remove to show only the active button and first, prev, next and last buttons:
                        if (($i != $page + 1) && ($i <= $page + 3) && ($i >= $page - 1)) {//This is set for two below and two above the current page
                            echo '<a href="index.php?page=' . $i . '"><button>' . $i . '</button></a>';
                        }
                    }

                    if (($page + 1) != $pages_total) {
                        echo '<a href="index.php?page=' . $page_up . '"><button>></button></a>'; //Button for next page [>]
                        echo '<a href="index.php?page=' . $pages_total . '"><button>>></button></a>'; //Button for last page [>>]
                    }
                    echo "</div>"; // #pageNav end

                    echo '<div id="gallery">'; // Gallery start
// DISPLAY THE images:
//Select the images from the table limited as per our $offet and $per_page total:
                    $result = mysqli_query($dbCon, "SELECT * FROM images ORDER by id ASC LIMIT $offset, $per_page");
                    while ($row = mysqli_fetch_array($result)) {//Open the while array loop
//Define the image variable:
                        $image = $row['file'];

                        echo '<div class="img-container">';
                        echo '<div class="img">';

                        echo '<a href="images/' . $image . '">';
                        echo '<img src="images/' . $image . '">';
                        echo '</a>';

                        echo '</div>';
                        echo '</div>'; // .img-container end
                    }//Close the while array loop

                    echo '</div>'; // Gallery end

                    echo '<div class="clearfix"></div>'; // Gallery end
                    ?>

                    <p class="ct">
                        <a href="https://coding-tips.com/" id="coding-tips">&#60;coding-tips.com&#62;</a>
                    </p>

                </div>
            </div>
            <footer>
                <div class="margin">
                    <div class="copyright">&copy; Copyright 2019 <a href="https://coding-tips.com">coding-tips.com</a>. All rights reserved.</div>
                </div>
            </footer>

        </div><!-- #main end -->

    </body>

    <script src="js/jquery-3.2.1.min.js"></script>

</html>