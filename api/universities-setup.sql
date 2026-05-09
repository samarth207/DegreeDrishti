-- =====================================================
-- DegreeDrishti — Universities Table
-- Run this in Hostinger phpMyAdmin (same DB as blogs)
-- =====================================================

CREATE TABLE IF NOT EXISTS `universities` (
    `id`                   INT(11)       NOT NULL AUTO_INCREMENT,
    `slug`                 VARCHAR(100)  NOT NULL,
    `name`                 VARCHAR(255)  NOT NULL,
    `short_name`           VARCHAR(50)   DEFAULT NULL,
    `logo`                 VARCHAR(500)  DEFAULT NULL,
    `established`          YEAR          DEFAULT NULL,
    `location`             VARCHAR(150)  DEFAULT NULL,
    `type`                 ENUM('Private','Government','Deemed') DEFAULT 'Private',
    `naac_grade`           VARCHAR(10)   DEFAULT NULL,
    `ugc_approved`         TINYINT(1)    DEFAULT 1,

    -- Rankings
    `rank_nirf`            SMALLINT      DEFAULT NULL,
    `rank_outlook`         SMALLINT      DEFAULT NULL,

    -- Fees
    `min_fee`              INT(11)       DEFAULT 0,
    `max_fee`              INT(11)       DEFAULT 0,

    -- Courses (JSON array of {name,duration,fee,totalFee,specializations[]})
    `courses_json`         JSON          DEFAULT NULL,

    -- Admission
    `admission_mode`       VARCHAR(50)   DEFAULT 'Online',
    `exams_accepted`       VARCHAR(500)  DEFAULT NULL,   -- comma-separated

    -- Highlights (comma-separated)
    `highlights`           TEXT          DEFAULT NULL,

    -- Placements
    `placement_rate`       TINYINT       DEFAULT NULL,
    `avg_salary`           DECIMAL(5,1)  DEFAULT NULL,
    `top_recruiters`       VARCHAR(500)  DEFAULT NULL,   -- comma-separated

    -- Features
    `emi_available`        TINYINT(1)    DEFAULT 0,
    `scholarship`          TINYINT(1)    DEFAULT 0,
    `lms_type`             VARCHAR(100)  DEFAULT NULL,
    `support_types`        VARCHAR(200)  DEFAULT NULL,   -- comma-separated

    -- Links
    `website_url`          VARCHAR(500)  DEFAULT NULL,
    `page_url`             VARCHAR(500)  DEFAULT NULL,   -- internal page (e.g. /amity-online-university)

    -- Meta
    `rating`               DECIMAL(3,1)  DEFAULT 0.0,
    `review_count`         INT(11)       DEFAULT 0,
    `featured`             TINYINT(1)    DEFAULT 0,
    `active`               TINYINT(1)    DEFAULT 1,
    `sort_order`           SMALLINT      DEFAULT 0,

    -- Audit
    `created_at`           DATETIME      DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           DATETIME      DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`),
    INDEX `idx_featured` (`featured`),
    INDEX `idx_active`   (`active`),
    INDEX `idx_type`     (`type`),
    INDEX `idx_min_fee`  (`min_fee`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Seed initial university data
-- =====================================================

INSERT INTO `universities`
    (`slug`,`name`,`short_name`,`logo`,`established`,`location`,`type`,`naac_grade`,`ugc_approved`,
     `rank_nirf`,`rank_outlook`,`min_fee`,`max_fee`,
     `courses_json`,`admission_mode`,`exams_accepted`,`highlights`,
     `placement_rate`,`avg_salary`,`top_recruiters`,
     `emi_available`,`scholarship`,`lms_type`,`support_types`,
     `website_url`,`page_url`,`rating`,`review_count`,`featured`,`sort_order`)
VALUES

('amity-online','Amity University Online','Amity','/images/university-logos/amity.png',2005,'Noida, UP','Private','A+',1,
 46,12,40000,75000,
 '[{"name":"MBA","duration":"2 Years","fee":75000,"totalFee":150000,"specializations":["Finance","Marketing","HR","IT","Business Analytics"]},{"name":"MCA","duration":"2 Years","fee":68000,"totalFee":136000,"specializations":["Data Science","Cloud Computing","Cybersecurity"]},{"name":"BBA","duration":"3 Years","fee":55000,"totalFee":165000,"specializations":["Finance","Marketing","Entrepreneurship"]},{"name":"BCA","duration":"3 Years","fee":52000,"totalFee":156000,"specializations":["Web Dev","AI & ML","Data Science"]},{"name":"BCom","duration":"3 Years","fee":48000,"totalFee":144000,"specializations":["Accounting","Taxation"]}]',
 'Online','AMIGAT,MAT,CAT,XAT','UGC-DEB Approved,NAAC A+ Accredited,IBM Partnered Curriculum,Live Interactive Classes,200+ Placement Partners',
 92,8.5,'Deloitte,KPMG,Amazon,Infosys,TCS,Wipro',
 1,1,'Amity LMS','Email,Phone,Chat,WhatsApp',
 'https://www.amityonline.com','/amity-online-university',4.5,8420,1,1),

('manipal-online','Manipal University Online','Manipal','/images/university-logos/manipal.png',1953,'Manipal, Karnataka','Private','A++',1,
 55,8,60000,115000,
 '[{"name":"MBA","duration":"2 Years","fee":115000,"totalFee":230000,"specializations":["Finance","Marketing","HR","Operations","Business Analytics","Healthcare"]},{"name":"MCA","duration":"2 Years","fee":90000,"totalFee":180000,"specializations":["Data Science","Cloud","Cybersecurity","AI"]},{"name":"BBA","duration":"3 Years","fee":75000,"totalFee":225000,"specializations":["Finance","Marketing","International Business"]},{"name":"BCA","duration":"3 Years","fee":70000,"totalFee":210000,"specializations":["Web Dev","Cloud","AI & ML"]}]',
 'Online','MAHE Entrance,MAT,CAT,Direct','UGC-DEB & AICTE Approved,NAAC A++ Accredited,Global Alumni Network (300K+),24x7 Learning Support,Dual Degree Options',
 94,10.2,'Google,Microsoft,Amazon,Accenture,HDFC,Reliance',
 1,1,'Manipal ProLearn LMS','Email,Phone,Chat,24x7 Support',
 'https://onlinemanipal.com','/manipal-online-university',4.7,12350,1,2),

('nmims-online','NMIMS Global Access School','NMIMS','/images/university-logos/nmims.png',1981,'Mumbai, Maharashtra','Deemed','A+',1,
 38,5,62000,185000,
 '[{"name":"MBA","duration":"2 Years","fee":98000,"totalFee":196000,"specializations":["Finance","Marketing","HR","Operations","SCM","Fintech"]},{"name":"BBA","duration":"3 Years","fee":72000,"totalFee":216000,"specializations":["Finance","Marketing","HR"]},{"name":"BCom","duration":"3 Years","fee":62000,"totalFee":186000,"specializations":["Accounting","Finance"]},{"name":"Executive MBA","duration":"1 Year","fee":185000,"totalFee":185000,"specializations":["General Management","Finance","Marketing"]}]',
 'Online','NMAT,MAT,CAT,Self Test','UGC-DEB Approved,NAAC A+ | AACSB Accredited,Mumbai Financial Hub Advantage,Live Mentorship by CXOs',
 91,11.5,'JP Morgan,Goldman Sachs,McKinsey,BCG,HDFC,SBI',
 1,1,'NMIMS LMS','Email,Phone,WhatsApp,Weekend Support',
 'https://www.nmimsga.in','/nmims-online-university',4.6,6280,1,3),

('lpu-online','Lovely Professional University Online','LPU','/images/university-logos/lpu.png',2005,'Phagwara, Punjab','Private','A+',1,
 30,7,42000,85000,
 '[{"name":"MBA","duration":"2 Years","fee":72000,"totalFee":144000,"specializations":["Finance","Marketing","HR","IT","Agribusiness"]},{"name":"MCA","duration":"2 Years","fee":65000,"totalFee":130000,"specializations":["AI & ML","Data Science","Cloud"]},{"name":"BBA","duration":"3 Years","fee":50000,"totalFee":150000,"specializations":["Finance","Marketing","HR","Entrepreneurship"]},{"name":"BCA","duration":"3 Years","fee":48000,"totalFee":144000,"specializations":["Web Dev","Cloud","AI","Cybersecurity"]},{"name":"BCom","duration":"3 Years","fee":42000,"totalFee":126000,"specializations":["Accounting","Finance","e-Commerce"]}]',
 'Online','LPUNEST,MAT,CAT,Direct Admission','UGC-DEB Approved,NAAC A+ | 700+ Acres Campus,World Record Holder University,500+ Industry Partners,Affordable & Scholarship Rich',
 90,7.8,'Amazon,Snapdeal,TCS,Infosys,Mahindra,HDFC',
 1,1,'UMS LPU','Email,Phone,Chat,WhatsApp',
 'https://online.lpu.in',NULL,4.4,15600,1,4),

('chandigarh-university-online','Chandigarh University Online','CU Online','/images/university-logos/chandigarh.png',2012,'Mohali, Punjab','Private','A+',1,
 41,10,48000,80000,
 '[{"name":"MBA","duration":"2 Years","fee":80000,"totalFee":160000,"specializations":["Finance","Marketing","HR","IT","Logistics","Healthcare Mgmt"]},{"name":"MCA","duration":"2 Years","fee":70000,"totalFee":140000,"specializations":["Data Science","AI & ML","Full Stack"]},{"name":"BBA","duration":"3 Years","fee":58000,"totalFee":174000,"specializations":["Finance","Marketing","Digital Marketing"]},{"name":"BCA","duration":"3 Years","fee":55000,"totalFee":165000,"specializations":["AI","Cloud","Web Dev"]}]',
 'Online','CUCET,MAT,CAT,Direct','UGC-DEB Approved,NAAC A+ Grade,IBM & Oracle Certified Labs,Internship Guaranteed Programs',
 93,9.1,'Microsoft,Capgemini,IBM,Accenture,HCL,Tech Mahindra',
 1,1,'CU Online LMS','Email,Phone,Chat,WhatsApp',
 'https://online.cuonline.in',NULL,4.5,10240,0,5),

('jain-online','Jain University Online','Jain Online','/images/university-logos/jain.png',1990,'Bengaluru, Karnataka','Deemed','A++',1,
 52,15,52000,85000,
 '[{"name":"MBA","duration":"2 Years","fee":85000,"totalFee":170000,"specializations":["Finance","Marketing","HR","Sports Management"]},{"name":"MCA","duration":"2 Years","fee":75000,"totalFee":150000,"specializations":["Data Science","Cloud","AI"]},{"name":"BBA","duration":"3 Years","fee":60000,"totalFee":180000,"specializations":["Finance","Marketing"]},{"name":"BCA","duration":"3 Years","fee":58000,"totalFee":174000,"specializations":["AI","Full Stack","Cybersecurity"]}]',
 'Online','JSAT,MAT,CAT,Direct','NAAC A++ Accredited,UGC-DEB Approved,Bengaluru IT Hub Advantage,Live & Recorded Lectures',
 89,8.8,'Infosys,Wipro,Deloitte,EY,KPMG,Cognizant',
 1,1,'Jain Online LMS','Email,Phone,Chat',
 'https://www.jainuniversityonline.in',NULL,4.3,5140,0,6),

('dpu-online','DY Patil University Online','DPU','/images/university-logos/dpu.png',2002,'Pune, Maharashtra','Deemed','A+',1,
 68,18,60000,90000,
 '[{"name":"MBA","duration":"2 Years","fee":90000,"totalFee":180000,"specializations":["Finance","Marketing","HR","Healthcare"]},{"name":"MCA","duration":"2 Years","fee":78000,"totalFee":156000,"specializations":["Data Science","AI","Cloud"]},{"name":"BCA","duration":"3 Years","fee":60000,"totalFee":180000,"specializations":["AI","Web Dev","IoT"]}]',
 'Online','DPUAT,MAT,Direct','UGC-DEB Approved,NAAC A+ Grade,Pune Silicon Valley Advantage,Healthcare Specializations',
 87,8.0,'Bajaj,Mahindra,HDFC,Cognizant,TCS,Wipro',
 1,1,'DPU Online LMS','Email,Phone,Chat',
 'https://online.dpu.edu.in',NULL,4.2,3780,0,7),

('ignou','IGNOU (Indira Gandhi National Open University)','IGNOU','/images/ignou-logo.png',1985,'New Delhi','Government','A',1,
 22,3,7000,22500,
 '[{"name":"MBA","duration":"2 Years","fee":22500,"totalFee":45000,"specializations":["General Management","Finance","Marketing","HR"]},{"name":"MCA","duration":"3 Years","fee":18000,"totalFee":54000,"specializations":["Computer Applications"]},{"name":"BCom","duration":"3 Years","fee":9000,"totalFee":27000,"specializations":["General","Financial and Cost Accounting"]}]',
 'Online','OPENMAT,Direct','Government University — Highly Affordable,UGC, DEB & AICTE Approved,World\'s Largest Open University,4.5 Million+ Enrolled Students',
 78,6.2,'Government Sectors,PSUs,NGOs,IT Companies',
 0,1,'eGyanKosh','Email,Regional Centres',
 'https://www.ignou.ac.in',NULL,4.1,22400,0,8),

('sharda-online','Sharda University Online','Sharda','/images/sharda-logo.png',2009,'Greater Noida, UP','Private','A',1,
 85,22,45000,68000,
 '[{"name":"MBA","duration":"2 Years","fee":68000,"totalFee":136000,"specializations":["Finance","Marketing","HR","IT"]},{"name":"MCA","duration":"2 Years","fee":58000,"totalFee":116000,"specializations":["Data Science","Cloud"]},{"name":"BBA","duration":"3 Years","fee":48000,"totalFee":144000,"specializations":["Finance","Marketing"]}]',
 'Online','Direct,MAT,CAT','UGC-DEB Approved,NAAC A Grade,International Collaborations,Value-for-Money Programs',
 85,7.2,'TCS,Infosys,HCL,Wipro,Amazon',
 1,1,'Sharda Online LMS','Email,Phone,Chat',
 'https://shardaonline.ac.in',NULL,4.0,3200,0,9),

('symbiosis-online','Symbiosis Centre for Distance Learning','Symbiosis','/images/university-logos/symbiosis.png',2001,'Pune, Maharashtra','Deemed','A',1,
 61,9,55000,95000,
 '[{"name":"MBA","duration":"2 Years","fee":95000,"totalFee":190000,"specializations":["Finance","Marketing","HR","Operations","IT"]},{"name":"BBA","duration":"3 Years","fee":68000,"totalFee":204000,"specializations":["Finance","Marketing","HR"]},{"name":"PGDBA","duration":"1 Year","fee":75000,"totalFee":75000,"specializations":["Business Analytics","Finance","Marketing"]}]',
 'Online','SNAP,MAT,Direct','UGC-DEB Approved,NAAC A Grade,Pune Prestigious Brand,Bloomberg Terminal Access,CFA & CMA Partnership',
 90,9.8,'Deloitte,PwC,EY,KPMG,Citibank,JP Morgan',
 1,0,'Symbiosis LMS','Email,Phone,Study Centres',
 'https://www.scdl.net',NULL,4.4,7100,0,10);
