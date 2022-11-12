DROP TABLE IF EXISTS wcf1_uzbot;
CREATE TABLE wcf1_uzbot (
    botID                        INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    botDescription                VARCHAR(255) NOT NULL,
    botTitle                    VARCHAR(80) NOT NULL,
    categoryID                    INT(10),
    enableLog                    TINYINT(1) NOT NULL DEFAULT 1,
    isDisabled                    TINYINT(1) NOT NULL DEFAULT 0,
    testMode                    TINYINT(1) NOT NULL DEFAULT 0,

    notifyID                    INT(10),
    notifyDes                    VARCHAR(30),
    articleCategoryID            INT(10),
    articleEnableComments        TINYINT(1) NOT NULL DEFAULT 0,
    articlePublicationStatus    TINYINT(1) NOT NULL DEFAULT 0,
    commentActivity                TINYINT(1) NOT NULL DEFAULT 0,
    conversationAllowAdd        TINYINT(1) NOT NULL DEFAULT 0,
    conversationClose            TINYINT(1) NOT NULL DEFAULT 0,
    conversationInvisible        TEXT,
    conversationLeave            TINYINT(1) NOT NULL DEFAULT 0,
    conversationType            TINYINT(1) NOT NULL DEFAULT 0,
    emailBCC                    TEXT,
    emailCC                        TEXT,
    emailAttachmentFile            VARCHAR(255) NOT NULL DEFAULT '',
    emailPrivacy                TINYINT(1) NOT NULL DEFAULT 0,

    isMultilingual                TINYINT(1) NOT NULL DEFAULT 0,
    notifyLanguageID            INT(10),
    receiverActive                TINYINT(1) NOT NULL DEFAULT 0,
    receiverAffected            TINYINT(1) NOT NULL DEFAULT 0,
    receiverNames                TEXT,
    receiverGroupIDs            TEXT,
    senderID                    INT(10),
    sendername                    VARCHAR(255) NOT NULL,

    typeID                        INT(10),
    typeDes                        VARCHAR(30),

    actionLabelDelete            TINYINT(1) NOT NULL DEFAULT 0,

    birthdayForce                TINYINT(1) NOT NULL DEFAULT 1,

    changeAffected                TINYINT(1) NOT NULL DEFAULT 0,
    condenseEnable                TINYINT(1) NOT NULL DEFAULT 0,
    condenseText                TEXT,

    articleConditionCategoryID    INT(10) DEFAULT 0,
    articlePublished            TINYINT(1) NOT NULL DEFAULT 0,

    cirData                        TEXT,
    cirExecution                TEXT,
    cirCounter                    INT(10),
    cirCounterInterval            INT(10),

    commentDays                    INT(10) NOT NULL DEFAULT 365,
    commentDaysAfter            VARCHAR(15) NOT NULL DEFAULT 'reply',
    commentNoAnswers            TINYINT(1) NOT NULL DEFAULT 0,
    commentNoUser                TINYINT(1) NOT NULL DEFAULT 0,
    commentTypeIDs                TEXT,

    conversationDays            INT(10) NOT NULL DEFAULT 365,
    conversationDaysAfter        VARCHAR(15) NOT NULL DEFAULT 'reply',
    conversationNoAnswers        TINYINT(1) NOT NULL DEFAULT 0,
    conversationNoLabels        TINYINT(1) NOT NULL DEFAULT 0,

    feedreaderExclude            TEXT,
    feedreaderFrequency            INT(10) NOT NULL DEFAULT 1800,
    feedreaderInclude            TEXT,
    feedreaderLast                INT(10) NOT NULL DEFAULT 0,
    feedreaderMaxAge            INT(10),
    feedreaderMaxItems            INT(10),
    feedreaderUrl                TEXT,
    feedreaderUseTime            TINYINT(1) NOT NULL DEFAULT 0,
    feedreaderUseTags            TINYINT(1) NOT NULL DEFAULT 0,

    groupAssignmentGroupID        INT(10),
    groupAssignmentAction        VARCHAR(10),
    groupChangeGroupIDs            TEXT,
    groupChangeType                TINYINT(1) NOT NULL DEFAULT 0,

    inactiveAction                VARCHAR(15) NOT NULL DEFAULT 'remind',
    inactiveBanReason            TEXT,
    inactiveReminderLimit        INT(10) NOT NULL DEFAULT 1,

    likeAction                    VARCHAR(15),

    userCount                    TEXT NOT NULL,
    userCreationGroupID            INT(10),
    userSettingAvatarOption        TINYINT(1) NOT NULL DEFAULT 0,
    userSettingCover            TINYINT(1) NOT NULL DEFAULT 0,
    userSettingEmail            TINYINT(1) NOT NULL DEFAULT 0,
    userSettingOther            TINYINT(1) NOT NULL DEFAULT 0,
    userSettingSelfDeletion        TINYINT(1) NOT NULL DEFAULT 0,
    userSettingSignature        TINYINT(1) NOT NULL DEFAULT 0,
    userSettingUsername            TINYINT(1) NOT NULL DEFAULT 0,
    userSettingUserTitle        TINYINT(1) NOT NULL DEFAULT 0,

    lastError                    INT(10) NOT NULL DEFAULT 0
);

DROP TABLE IF EXISTS wcf1_uzbot_log;
CREATE TABLE wcf1_uzbot_log (
    logID                INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    time                INT(10) NOT NULL DEFAULT 0,
    botID                INT(10) NOT NULL,
    botTitle             VARCHAR(80) NOT NULL DEFAULT '',
    count                INT(10) NOT NULL DEFAULT 0,
    typeID                INT(10) NOT NULL,
    typeDes                VARCHAR(80) NOT NULL DEFAULT '',
    notifyDes            VARCHAR(80) NOT NULL DEFAULT '',
    status                TINYINT(1) DEFAULT 0,
    testMode            TINYINT(1) NOT NULL DEFAULT 0,
    additionalData        TEXT,

    KEY (botID),
    KEY (typeID)
);

DROP TABLE IF EXISTS wcf1_uzbot_feedreader_hash;
CREATE TABLE wcf1_uzbot_feedreader_hash (
    botID                INT(10) NOT NULL,
    hash                VARCHAR(40) NOT NULL DEFAULT '',

    UNIQUE KEY (botID, hash)
);

DROP TABLE IF EXISTS wcf1_uzbot_notify;
CREATE TABLE wcf1_uzbot_notify (
    id                    INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,

    notifyID            INT(10) DEFAULT 0,
    notifyTitle            VARCHAR(30) DEFAULT '',

    hasContent            TINYINT(1) DEFAULT 0,
    hasLabels            TINYINT(1) DEFAULT 0,
    hasSender            TINYINT(1) DEFAULT 0,
    hasSubject            TINYINT(1) DEFAULT 0,
    hasReceiver            TINYINT(1) DEFAULT 0,
    hasTags                TINYINT(1) DEFAULT 0,
    hasTeaser            TINYINT(1) DEFAULT 0,
    neededModule        VARCHAR(40) NOT NULL DEFAULT '',
    notifyFunction        VARCHAR(255) NOT NULL DEFAULT '',
    packageID            INT(10) NOT NULL,
    sortOrder            INT(10) NOT NULL,

    UNIQUE KEY (notifyID),
    UNIQUE KEY (sortOrder)
);

DROP TABLE IF EXISTS wcf1_uzbot_content;
CREATE TABLE wcf1_uzbot_content (
    contentID            INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    botID                INT(10),
    languageID            INT(10),
    condense            MEDIUMTEXT,
    content                MEDIUMTEXT,
    subject                VARCHAR(255) DEFAULT '',
    tags                TEXT,
    teaser                TEXT,
    imageID                INT(10) DEFAULT NULL,
    teaserImageID        INT(10) DEFAULT NULL,

    UNIQUE KEY (botID, languageID)
);

DROP TABLE IF EXISTS wcf1_uzbot_type;
CREATE TABLE wcf1_uzbot_type (
    id                    INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    typeID                INT(10) NOT NULL DEFAULT 0,
    typeTitle            VARCHAR(30) NOT NULL DEFAULT '',

    application            VARCHAR(15) NOT NULL DEFAULT '',
    canCondense            TINYINT(1) NOT NULL DEFAULT 0,
    hasAffected            TINYINT(1) DEFAULT 0,
    allowGuest            TINYINT(1) DEFAULT 0,
    canChangeAffected    TINYINT(1) DEFAULT 0,
    needCount            TINYINT(1) DEFAULT 0,
    needCountAction        VARCHAR(25) NOT NULL DEFAULT '',
    needCountNo            VARCHAR(255) NOT NULL DEFAULT '',
    neededModule        VARCHAR(40) NOT NULL DEFAULT '',
    needNotify            TINYINT(1) NOT NULL DEFAULT 0,
    packageID            INT(10) NOT NULL,
    sortOrder            INT(10) NOT NULL,

    UNIQUE KEY (typeID),
    KEY (sortOrder)
);

DROP TABLE IF EXISTS wcf1_uzbot_top;
CREATE TABLE wcf1_uzbot_top (
    topID            INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    comment            INT(10) DEFAULT NULL,
    followed        INT(10) DEFAULT NULL,
    liked            INT(10) DEFAULT NULL,
    disliked        INT(10) DEFAULT NULL,
    attachment        INT(10) DEFAULT NULL
);

DROP TABLE IF EXISTS wcf1_uzbot_stats;
CREATE TABLE wcf1_uzbot_stats (
    id                    INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    time                INT(10) DEFAULT 0,

    articleTotal        INT(10) DEFAULT 0,
    articleComments        INT(10) DEFAULT 0,
    articleLikes        INT(10) DEFAULT 0,
    articlePublished    INT(10) DEFAULT 0,
    articleUnpublished    INT(10) DEFAULT 0,
    articleViews        INT(10) DEFAULT 0,
    attachment            INT(10) DEFAULT 0,
    attachmentSize        BIGINT DEFAULT 0,
    attachmentDownload    INT(10) DEFAULT 0,
    comment                INT(10) DEFAULT 0,
    commentReply        INT(10) DEFAULT 0,
    conversation        INT(10) DEFAULT 0,
    conversationMsg        INT(10) DEFAULT 0,
    dislikes            INT(10) DEFAULT 0,
    follower            INT(10) DEFAULT 0,
    likes                INT(10) DEFAULT 0,
    userBanned            INT(10) DEFAULT 0,
    userDeleted            INT(10) DEFAULT 0,
    userDisabled        INT(10) DEFAULT 0,
    userTotal            INT(10) DEFAULT 0
);
INSERT INTO wcf1_uzbot_stats (userTotal) VALUES (0);

DROP TABLE IF EXISTS wcf1_uzbot_system;
CREATE TABLE wcf1_uzbot_system (
    id                INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    botID            INT(10),
    email            VARCHAR(255) NOT NULL,
    userID            INT(10),
    username        VARCHAR(255) NOT NULL,
    languageID        INT(10),
    counter            INT(10)
);

-- foreign keys
ALTER TABLE wcf1_uzbot ADD FOREIGN KEY (categoryID) REFERENCES wcf1_category (categoryID) ON DELETE SET NULL;
ALTER TABLE wcf1_uzbot ADD FOREIGN KEY (senderID) REFERENCES wcf1_user (userID) ON DELETE SET NULL;
ALTER TABLE wcf1_uzbot ADD FOREIGN KEY (articleCategoryID) REFERENCES wcf1_category (categoryID) ON DELETE SET NULL;
ALTER TABLE wcf1_uzbot ADD FOREIGN KEY (groupAssignmentGroupID) REFERENCES wcf1_user_group (groupID) ON DELETE SET NULL;

-- content
ALTER TABLE wcf1_uzbot_content ADD FOREIGN KEY (botID) REFERENCES wcf1_uzbot (botID) ON DELETE CASCADE;
ALTER TABLE wcf1_uzbot_content ADD FOREIGN KEY (imageID) REFERENCES wcf1_media (mediaID) ON DELETE SET NULL;
ALTER TABLE wcf1_uzbot_content ADD FOREIGN KEY (teaserImageID) REFERENCES wcf1_media (mediaID) ON DELETE SET NULL;

-- log
ALTER TABLE wcf1_uzbot_log ADD FOREIGN KEY (botID) REFERENCES wcf1_uzbot (botID) ON DELETE CASCADE;
ALTER TABLE wcf1_uzbot_log ADD FOREIGN KEY (typeID) REFERENCES wcf1_uzbot_type (typeID) ON DELETE CASCADE;

-- notify
ALTER TABLE wcf1_uzbot_notify ADD FOREIGN KEY (packageID) REFERENCES wcf1_package (packageID) ON DELETE CASCADE;
ALTER TABLE wcf1_uzbot ADD FOREIGN KEY (notifyID) REFERENCES wcf1_uzbot_notify (notifyID) ON DELETE SET NULL;

-- type
ALTER TABLE wcf1_uzbot_type ADD FOREIGN KEY (packageID) REFERENCES wcf1_package (packageID) ON DELETE CASCADE;
ALTER TABLE wcf1_uzbot ADD FOREIGN KEY (typeID) REFERENCES wcf1_uzbot_type (typeID) ON DELETE SET NULL;
-- content

-- hash
ALTER TABLE wcf1_uzbot_feedreader_hash ADD FOREIGN KEY (botID) REFERENCES wcf1_uzbot (botID) ON DELETE CASCADE;

-- top
ALTER TABLE wcf1_uzbot_top ADD FOREIGN KEY (comment) REFERENCES wcf1_user (userID) ON DELETE SET NULL;
ALTER TABLE wcf1_uzbot_top ADD FOREIGN KEY (followed) REFERENCES wcf1_user (userID) ON DELETE SET NULL;
ALTER TABLE wcf1_uzbot_top ADD FOREIGN KEY (liked) REFERENCES wcf1_user (userID) ON DELETE SET NULL;
ALTER TABLE wcf1_uzbot_top ADD FOREIGN KEY (disliked) REFERENCES wcf1_user (userID) ON DELETE SET NULL;
ALTER TABLE wcf1_uzbot_top ADD FOREIGN KEY (attachment) REFERENCES wcf1_user (userID) ON DELETE SET NULL;

-- notification
-- no foreign key on userID and languageID by purpose
ALTER TABLE wcf1_uzbot_system ADD FOREIGN KEY (botID) REFERENCES wcf1_uzbot (botID) ON DELETE CASCADE;
