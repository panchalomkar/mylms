<?php
defined('MOODLE_INTERNAL') || die();

// Core
$string['pluginname']  = 'Hall of Fame';
$string['halloffame']  = 'Hall of Fame';

// Navigation / headings
$string['awards']            = 'Awards';
$string['achievements']      = 'Achievements Gallery';
$string['submit']            = 'Upload Certificate';
$string['adminpanel']        = 'Admin Panel';
$string['review']            = 'Review Submissions';
$string['mysubmissions']     = 'My Submissions';
$string['managecategories']  = 'Manage Categories';
$string['managedepartments'] = 'Manage Departments';

// Awards form
$string['createaward']           = 'Create Award';
$string['awardtitle']            = 'Award Title';
$string['department']            = 'Department';
$string['category']              = 'Category';
$string['month']                 = 'Month';
$string['year']                  = 'Year';
$string['selectlevel']           = 'Select Level';
$string['selectdepartment']      = 'Select Department';
$string['selectcategory']        = 'Select Category';
$string['selectmonth']           = 'Select Month';
$string['selectachiever']        = 'Select Achiever';
$string['messagetodisplay']      = 'Message To Display';
$string['uploadcertificate']     = 'Upload Certificate';
$string['uploadachieversimage']  = "Upload Achiever's Image";
$string['preview']               = 'Preview';
$string['post']                  = 'Post';
$string['congratulations']       = 'Congratulations!!!';
$string['renamecategory']        = 'Rename Category';

// Achievements / submissions
$string['earnedcertificate']         = 'earned a certificate in';
$string['certificatetitle']          = 'Certificate Title';
$string['issuingorganisation']       = 'Issuing Organisation';
$string['dateofissue']               = 'Date Of Issue';
$string['certificatetype']           = 'Certificate Type';
$string['additionalnotes']           = 'Additional Notes';
$string['externalcertificate']       = 'External Certificate';
$string['uploadexternalcertificate'] = 'Upload External Certificate';
$string['submitforreview']           = 'Submit For Review';
$string['submissionnote']            = 'Note: Certificates uploaded successfully will be sent for admin review. Once approved, they will appear in your profile and the Achievement Gallery.';

// Admin review
$string['adminreview']  = 'Admin Review';
$string['approve']      = 'Approve';
$string['reject']       = 'Reject';
$string['pending']      = 'Pending';
$string['approved']     = 'Approved';
$string['rejected']     = 'Rejected';
$string['nosubmissions'] = 'No items to display.';

// Filters
$string['filterby']       = 'Filter By';
$string['allmonths']      = 'All Months';
$string['alldepartments'] = 'All Departments';
$string['allcategories']  = 'All Categories';
$string['quarter']        = 'Quarter';
$string['allquarters']    = 'All Quarters';
$string['allyears']       = 'All Years';

// Likes
$string['like']          = 'Like';
$string['likes']         = 'Likes';
$string['likeadded']     = 'Like added';
$string['likeremoved']   = 'Like removed';
$string['duplicatelike'] = 'You have already liked this item.';

// Months
$string['january']   = 'January';
$string['february']  = 'February';
$string['march']     = 'March';
$string['april']     = 'April';
$string['may']       = 'May';
$string['june']      = 'June';
$string['july']      = 'July';
$string['august']    = 'August';
$string['september'] = 'September';
$string['october']   = 'October';
$string['november']  = 'November';
$string['december']  = 'December';

// Certificate types
$string['type_technical']  = 'Technical';
$string['type_management'] = 'Project Management';
$string['type_leadership'] = 'Leadership';
$string['type_compliance'] = 'Compliance';
$string['type_other']      = 'Other';

// Success / error messages
$string['awardsaved']          = 'Award created successfully.';
$string['submissionsaved']     = 'Certificate submitted for review.';
$string['achievementapproved'] = 'Achievement approved and published.';
$string['achievementrejected'] = 'Submission rejected.';
$string['categorysaved']       = 'Category saved.';
$string['departmentsaved']     = 'Department saved.';
$string['accessdenied']        = 'Access denied.';
$string['invalidrequest']      = 'Invalid request.';
$string['fileuploaderror']     = 'File upload error. Please try again.';
$string['filetypeerror']       = 'Only PDF, JPG and PNG files are allowed.';
$string['filesizeerror']       = 'File exceeds the maximum allowed size.';

// Settings page
$string['settings_general']            = 'General';
$string['enable_likes']                = 'Enable Likes';
$string['enable_likes_desc']           = 'Allow users to like awards and achievements.';
$string['enable_submissions']          = 'Enable User Submissions';
$string['enable_submissions_desc']     = 'Allow users to submit external certificates for admin review.';
$string['max_filesize_mb']             = 'Max Upload Size (MB)';
$string['max_filesize_mb_desc']        = 'Maximum file size in MB for certificate uploads. Default: 5.';
$string['allowed_filetypes']           = 'Allowed File Types';
$string['allowed_filetypes_desc']      = 'Comma-separated extensions without dot. Example: pdf,jpg,png';
$string['cards_per_row']               = 'Award Cards Per Row';
$string['cards_per_row_desc']          = 'Number of award cards displayed per row in the grid.';
$string['sort_order']                  = 'Default Sort Order';
$string['sort_order_desc']             = 'How items are sorted by default.';
$string['settings_notifications']      = 'Email Notifications';
$string['notify_on_submission']        = 'Notify Admins on New Submission';
$string['notify_on_submission_desc']   = 'Email users with the approve capability when a certificate is submitted.';
$string['notify_user_on_approval']     = 'Notify User on Approval/Rejection';
$string['notify_user_on_approval_desc'] = 'Email the user when their submission is approved or rejected.';
$string['notify_user_on_award']        = 'Notify User When Awarded';
$string['notify_user_on_award_desc']   = 'Email the recipient when an admin creates an award for them.';
$string['settings_display']            = 'Display';
$string['show_in_nav']                 = 'Show in Navigation';
$string['show_in_nav_desc']            = 'Display a Hall of Fame link in the primary navigation menu.';
$string['nav_label']                   = 'Navigation Label';
$string['nav_label_desc']              = 'Custom navigation label. Leave blank to use the plugin default.';

// Privacy
$string['privacy:metadata:halloffame_awards']       = 'Information about awards given to users.';
$string['privacy:metadata:halloffame_achievements'] = 'Information about approved user achievements.';
$string['privacy:metadata:halloffame_submissions']  = 'Certificate submissions made by users.';
$string['privacy:metadata:halloffame_likes']        = 'Likes placed by users on awards and achievements.';

// IOMAD / multi-tenancy
$string['companycontext']            = 'Viewing: {$a}';
$string['allcompanies']              = 'All Companies (Site Admin)';
$string['iomad_heading']             = 'IOMAD & Multi-Tenancy';
$string['dept_profile_field']        = 'Department profile field shortname';
$string['dept_profile_field_desc']   = 'Shortname of the Moodle custom user profile field used as the department source. Default: department.';

// Year filter label (used in filter bar)
$string['year']                      = 'Year';

// New UI strings
$string['noawards']                  = 'No awards to display yet.';
$string['noachievements']            = 'No achievements to display yet.';
$string['filterresults']             = 'Filter results';
$string['viewcertificate']           = 'View Certificate';
$string['downloadcertificate']       = 'Download Certificate';
