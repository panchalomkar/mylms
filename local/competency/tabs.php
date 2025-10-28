<div class="row" id="competencytabs">
    <div class="col-md-12">
        <ul class="nav nav-tabs styled-tabs" id="myTab" role="tablist">
            <?php
            $context = context_system::instance();
            if (has_capability('local/competency:managemainheading', $context)) { ?>
                <li class="nav-item">
                    <a class="nav-link <?php if ($activepage == 'mainheading') {
                        echo 'active';
                    } ?>" href="mainheading.php">
                        <i class="fa fa-sitemap me-1"></i> Main Competency
                    </a>
                </li>
            <?php } ?>
            <?php
            if (has_capability('local/competency:uploadcompetency', $context)) { ?>
                <li class="nav-item">
                    <a class="nav-link <?php if ($activepage == 'uploadcsv') {
                        echo 'active';
                    } ?>" href="uploadcompetency.php">
                        <i class="fa fa-upload me-1"></i> Upload CSV
                    </a>
                </li>
            <?php } ?>
            <?php
            if (has_capability('local/competency:managesubcompetency', $context)) { ?>
                <li class="nav-item">
                    <a class="nav-link <?php if ($activepage == 'subcompetency') {
                        echo 'active';
                    } ?>"
                        href="subcompetency.php">
                        <i class="fa fa-list-alt me-1"></i> Sub Competency
                    </a>
                </li>
            <?php } ?>
            <?php
            if (has_capability('local/competency:managesubsubcompetency', $context)) { ?>
                <li class="nav-item">
                    <a class="nav-link <?php if ($activepage == 'subsubcompetency') {
                        echo 'active';
                    } ?>"
                        href="subsubcompetency.php">
                        <i class="fa fa-indent me-1"></i> Sub Sub Competency
                    </a>
                </li>
            <?php } ?>
            <?php
            if (has_capability('local/competency:viewcompetency', $context)) { ?>
                <li class="nav-item">
                    <a class="nav-link <?php if ($activepage == 'viewcompetency') {
                        echo 'active';
                    } ?>"
                        href="viewcompetency.php">
                        <i class="fa fa-eye me-1"></i> View Performance
                    </a>
                </li>
            <?php } ?>
            <?php
            if (has_capability('local/competency:competencyapproval', $context)) { ?>
                <li class="nav-item">
                    <a class="nav-link <?php if ($activepage == 'approval') {
                        echo 'active';
                    } ?>" href="approval.php">
                        <i class="fa fa-check-square-o me-1"></i> Assign Competency
                    </a>
                </li>
            <?php } ?>
            <?php
            if (has_capability('local/competency:managerrating', $context)) { ?>
                <li class="nav-item">
                    <a class="nav-link <?php if ($activepage == 'managerrating') {
                        echo 'active';
                    } ?>"
                        href="managerrating.php">
                        <i class="fa fa-user-circle me-1"></i> Manager Rating
                    </a>
                </li>
            <?php } ?>
            <?php
            if (has_capability('local/competency:userselfrating', $context)) { ?>
                <li class="nav-item">
                    <a class="nav-link <?php if ($activepage == 'usersrating') {
                        echo 'active';
                    } ?>" href="userselfrating.php">
                        <i class="fa fa-user me-1"></i> User's Self Rating
                    </a>
                </li>
            <?php } ?>
            <?php
            if (has_capability('local/competency:landdrating', $context)) { ?>
                <li class="nav-item">
                    <a class="nav-link <?php if ($activepage == 'landdrating') {
                        echo 'active';
                    } ?>" href="landdrating.php">
                        <i class="fa fa-graduation-cap me-1"></i> L and D Rating
                    </a>
                </li>
            <?php } ?>
            <?php
            if (has_capability('local/competency:landdreport', $context)) { ?>
                <li class="nav-item">
                    <a class="nav-link <?php if ($activepage == 'userwisereport') {
                        echo 'active';
                    } ?>"
                        href="userwisereport.php">
                        <i class="fa fa-bar-chart me-1"></i> L and D Report
                    </a>
                </li>
            <?php } ?>
            <li class="nav-item">
                <a class="nav-link <?php if ($activepage == 'userreport') {
                    echo 'active';
                } ?>" href="userreport.php">
                    <i class="fa fa-file-text-o me-1"></i> Self Report
                </a>
            </li>
            <?php
            if (has_capability('local/competency:maangerreport', $context)) { ?>
                <li class="nav-item">
                    <a class="nav-link <?php if ($activepage == 'managerwisereport') {
                        echo 'active';
                    } ?>"
                        href="managerwisereport.php">
                        <i class="fa fa-pie-chart me-1"></i> Manager Report
                    </a>
                </li>
            <?php } ?>
        </ul>
    </div>
</div>