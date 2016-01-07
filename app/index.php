<?php
require_once dirname(__FILE__).'/../src/config.php';
$migrations = Process\Server::getInstance()->getSql();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Nasgrate - show SQL</title>
    <link rel="stylesheet" href="/public/bootstrap.min.css">
    <link rel="stylesheet" href="/public/default.min.css">
    <script src="/public/highlight.min.js"></script>
    <script src="/public/jquery-1.11.3.min.js"></script>
</head>

<body>

<!-- Begin page content -->
<div class="container">
    <div class="page-header">
        <h1>Nasgrate</h1>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="list-group">
                <?php foreach ($migrations as $m) { ?>
                    <a href="javascript:void(0);"
                       class="list-group-item <?php echo $m->isExecuted() ? 'executed' : ''; ?>"
                       migrationId="<?php echo $m->getMigrationId(); ?>"><?php echo $m->getClearName(); ?><br/>
                        <small><?php echo $m->getDate(); ?></small>
                    </a>
                <?php } ?>
            </div>
            <button class="btn btn-default btn-lg show-all">Show all migration</button>
        </div>
        <div class="col-md-9">
            <?php foreach ($migrations as $m) { ?>
                <div class="sql-item migration<?php echo $m->getMigrationId(); ?>">
                    <a name="<?php echo $m->getMigrationId(); ?>"></a>

                    <div class="row alert bg-<?php echo $m->isExecuted() ? 'success' : 'warning'; ?>">
                        <p class="lead"><b>Name:</b> <?php echo $m->getMigrationId(); ?></p>
                        <dl class="dl-horizontal">
                            <dt>Create date</dt>
                            <dd><?php echo $m->getDate(); ?></dd>
                            <dt>Is skip</dt>
                            <dd><?php echo $m->isSkip() ? 'yes' : 'no'; ?></dd>
                            <dt>Is executed</dt>
                            <dd><?php echo $m->isExecuted() ? 'yes' : 'no'; ?></dd>
                            <dt>Description</dt>
                            <dd><?php echo $m->getDescription(); ?></dd>
                        </dl>
                        <?php foreach ($m->getUpSql() as $k => $sql) { ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <pre><code class="sql"><?php echo $sql; ?></code></pre>
                                </div>
                                <div class="col-md-6">
                                    <pre><code class="sql"><?php echo $m->getDownSqlItem($k); ?></code></pre>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                    <p>&nbsp;</p>
                </div>
            <?php } ?>
        </div>
    </div>

    <script>
        hljs.initHighlightingOnLoad();
        $('.sql-item').hide();
        $('.sql-item:first').show();
        $('.list-group a').removeClass('active');
        $('.list-group a:first').addClass('active');

        $('.list-group .list-group-item').click(function () {
            $('.sql-item').hide();
            $('div.migration' + $(this).attr('migrationId')).show();
            $('.list-group .list-group-item').removeClass('active');
            $(this).addClass('active');
        });


        $('.show-all').click(function () {
            $('.col-md-3').remove();
            $('.col-md-9').removeClass('col-md-9').addClass('col-md-12');
            $('.sql-item').show();
        });
    </script>

</div>

</body>