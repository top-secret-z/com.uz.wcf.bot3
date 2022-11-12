### The 'Swiss Knife' for your community.

The WoltLab Suite offers only few possibilities to automate the maintenance of the community and the closer supervision of the members. Much has to be done manually, which takes a lot of time and also carries the risk of simply forgetting things.  **Community Bot**  can save you a lot of tedious work and can reliably do it for you.

### Function

Due to the large scope of functions and the various configuration possibilities, a detailed description would be beyond the scope here. At this point only so much: By creating individual bots, the  **Community Bot** allows you to monitor many processes, from changing the signature by a user to new registrations and birthdays and other members' anniversaries, right up to the user's activity. It can import content, welcome users, congratulate users on jubilees, and it can send timed and, if necessary, recurring notifications; it can encourage inactive users to participate again in the community; it can assign users to user groups depending based on many parameters or even delete them, and so on and so on.

Usually conditions can be used to define which users are to be affected by actions and which users are to be notified and which are not.

Currently, the following actions are available:

Basic Package

-   Feedreader
-   System - Error
-   System - Comments
-   System - Conversations
-   System - Reportings
-   System - Circular
-   System - Statistics
-   System - Updates
-   Article - Change
-   Article - New Article
-   User - Settings
-   User - Birthday
-   User - Total Number
-   User - Group Changes
-   User - Group Assignment
-   User - Inactivity
-   User - Likes and Dislikes
-   User - Membership
-   User - New User
-   User - Warning

Forum Extension

-   Forum - Post - Change by Author
-   Forum - Post - Number of Posts
-   Forum - Post - Moderation
-   Forum - Thread - Best Answer
-   Forum - Thread - Moderation
-   Forum - Thread - Modification
-   Forum - Thread - New
-   Forum - Statistics
-   Forum - Top Poster

Blog Extension

-   Blog - Article - Change by Author
-   Blog - Article - Number of Articles
-   Blog - Article - New
-   Blog - Blog - Change by Author
-   Blog - Blog - New
-   Blog - Statistics
-   Blog - Top Blogger

The types of notification currently available are:

Basic Package

-   System notification
-   Article (mono- and multilingual)
-   Email
-   Comment (wall)
-   Conversation (individual or group conversation)

Forum Extension

-   Forum - Post
-   Forum - Thread

Blog Extension

-   Blog - Article

Features introduced with new WSC versions, e.g. Trophies in WSC 3.1, will be included in new  **Community Bot** versions.

The language of the notifications can be freely selected or automatically determined. In addition, notifications can be created in parallel in all installed languages. A variety of placeholders can be used to automatically insert action-related information, e.g. usernames or links.

  
The Background Queue introduced with the WoltLab Suite is used to create notifications. If many notifications are to be created, they are first saved in the database and then are processed successively in the background. Because visits to the web page are required for this proccessing, you should set up a proper cronjob on your server to speed up the processing of the notifications. See e.g. here:  [Cronjob](https://community.woltlab.com/thread/253997-sofortige-e-mail-benachrichtigung-php-smtp/?postID=1585459&amp;highlight=queue#post1585459).

All  **Community Bot** actions can, if required, be logged and reviewed in the ACP.

### Configurability

Depending on the selected action and notification (aka Bots) different, partly very extensive and complex configuration options are available. A context-sensitive help facilitates the work with the  **Community Bot**. Additionally, the configuration of the Bots can be checked by means of a test mode during operation. The results are presented in the  **Community Bot**  log (ACP).

It is recommended that a bot user who performs actions and/or creates notifications be granted all necessary user permissions.

### Expandability

The  **Community Bot** can be expanded by free optional packages for WoltLab Suite applications and other plugins. Currently, packages for WoltLab Suite Forum and Blog, for VieCode Lexicon, WoltLab Suite: Timeline and News are already available, which are delivered with the basic package.