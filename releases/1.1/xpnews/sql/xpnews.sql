CREATE TABLE `xpnews_newsgroups` (                   
                          `id` INT(12) UNSIGNED NOT NULL AUTO_INCREMENT,          
                          `server_id` INT(12) UNSIGNED DEFAULT NULL,              
                          `newsgroup` VARCHAR(255) DEFAULT NULL,                  
                          `posts` INT(11) UNSIGNED DEFAULT '0',                   
                          `description` MEDIUMTEXT,                               
                          PRIMARY KEY (`id`)                                      
                        ) ENGINE=MYISAM;

CREATE TABLE `xpnews_servers` (                     
                       `server_id` INT(12) UNSIGNED NOT NULL AUTO_INCREMENT,  
                       `name` VARCHAR(128) DEFAULT NULL,                      
                       `server` VARCHAR(255) DEFAULT NULL,                    
                       `group` MEDIUMTEXT,                                    
                       `option` VARCHAR(255) DEFAULT NULL,                    
                       `auth_username` VARCHAR(128) DEFAULT NULL,             
                       `auth_password` VARCHAR(128) DEFAULT NULL,             
                       `charset` VARCHAR(20) DEFAULT NULL,                    
                       PRIMARY KEY (`server_id`)                              
                     ) ENGINE=MYISAM;
