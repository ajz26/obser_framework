<?php

add_filter( 'wp_mail_content_type',array('OBSER\Classes\Mails\Controller','set_content_type') );



