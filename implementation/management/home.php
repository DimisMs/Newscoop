<?php
require_once($_SERVER['DOCUMENT_ROOT']."/classes/config.php");
require_once($_SERVER['DOCUMENT_ROOT']."/classes/common.php");
require_once($_SERVER['DOCUMENT_ROOT']."/classes/Publication.php");
require_once($_SERVER['DOCUMENT_ROOT']."/classes/Issue.php");
require_once($_SERVER['DOCUMENT_ROOT']."/classes/Section.php");
require_once($_SERVER['DOCUMENT_ROOT']."/classes/Article.php");
require_once($_SERVER['DOCUMENT_ROOT']."/classes/Language.php");
require_once($_SERVER['DOCUMENT_ROOT']."/$ADMIN_DIR/CampsiteInterface.php");

load_common_include_files("$ADMIN_DIR");
list($access, $User) = check_basic_access($_REQUEST);	
if (!$access) {
	header("Location: /$ADMIN/logout.php");
	exit;
}

// "What" means "what to display".
// A value of "1" means "display Your Articles".
// A value of "0" means "display Submitted Articles".
if ($User->hasPermission("ChangeArticle")) {
	todefnum('What',0);
}
else {
	todefnum('What',1);
}
todefnum('NArtOffs');
if ($NArtOffs<0) {
	$NArtOffs=0;
}
todefnum('ArtOffs');
if ($ArtOffs < 0) {
	$ArtOffs=0; 
}
$NumDisplayArticles=15;
list($YourArticles, $NumYourArticles) = Article::GetArticlesByUser($User->getId(), $ArtOffs, 
	$NumDisplayArticles);

list($SubmittedArticles, $NumSubmittedArticles) = Article::GetSubmittedArticles($NArtOffs, $NumDisplayArticles);

$publications =& Publication::GetAllPublications();
$issues = array();
foreach ($publications as $publication) {
	$issues[$publication->getPublicationId()] =
		Issue::GetIssuesInPublication($publication->getPublicationId(), null, 
			array('LIMIT' => 5, 'ORDER BY' => array('Number' => 'DESC')));
}
$sections = array();
if ((count($publications) + count($issues)) < 12) {
	foreach ($publications as $publication) {
		foreach ($issues[$publication->getPublicationId()] as $issue) {
			$sections[$issue->getIssueId()] = 
				Section::GetSectionsInIssue($issue->getPublicationId(), $issue->getIssueId(),
					$issue->getLanguageId());
		}
	}
}
?>
<HEAD>
	<LINK rel="stylesheet" type="text/css" href="<?php echo $Campsite["website_url"] ?>/css/admin_stylesheet.css">
	<TITLE><?php  putGS("Home"); ?></TITLE>
</HEAD>
<BODY  BGCOLOR="WHITE" TEXT="BLACK" LINK="DARKBLUE" ALINK="RED" VLINK="DARKBLUE">
<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="1" WIDTH="100%" class="page_title_container">
<TR>
	<TD class="page_title" width="1%">
	    <?php  putGS("Home"); ?>
	</TD>
	<TD style="font-size: 9pt; padding-right: 10px; padding-top: 0px; padding-bottom: 2px;" valign="bottom" align="right"><?php  putGS('Welcome $1!','<B>'.htmlspecialchars($User->getName()).'</B>'); ?></TD>
</TR>
</TABLE>

<TABLE BORDER="0" CELLSPACING="4" CELLPADDING="2" WIDTH="100%">
<TR>
    <TD VALIGN="TOP" width="40%">
		<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="3" class="table_list" width="100%" style="padding: 0px;">
		<tr class="table_list_header">
			<td style="padding-left: 8px;"><?php putGS('Publication'); ?> / <?php putGS('Issue'); ?> <?php if (count($sections) > 0) { ?> / <?php putGS('Section');  } ?></td>
		</tr>
		<?php 
		$count = 1;
		foreach ($publications as $publication) { 
			$publicationId = $publication->getPublicationId();
			?>
			<tr <?php if (($count++%2)==1) {?> class="list_row_odd"<?php } else { ?>class="list_row_even"<?php } ?>>
				<td style="padding-left: 8px;"><a href="/<?php echo $ADMIN; ?>/pub/issues/?Pub=<?php p($publicationId); ?>"><?php p(htmlspecialchars($publication->getName())); ?></a></td>
			</tr>
			<?PHP
			if (isset($issues[$publicationId])) {
				foreach ($issues[$publicationId] as $issue) { ?>
					<tr <?php if (($count++%2)==1) {?> class="list_row_odd"<?php } else { ?>class="list_row_even"<?php } ?>>
						<td style="padding-left: 25px;"><a href="/<?php echo $ADMIN; ?>/pub/issues/sections/?Pub=<?php p($publicationId); ?>&Issue=<?php  p($issue->getIssueId()); ?>&Language=<?php p($issue->getLanguageId()); ?>"><?php p(htmlspecialchars($issue->getName())); ?></a></td>
					</tr>
					<?php 
					if (isset($sections[$issue->getIssueId()])) {
						foreach ($sections[$issue->getIssueId()] as $section) { ?>
							<tr <?php if (($count++%2)==1) {?> class="list_row_odd"<?php } else { ?>class="list_row_even"<?php } ?>>
								<td style="padding-left: 50px;"><a href="/<?php echo $ADMIN; ?>/pub/issues/sections/articles/?Pub=<?php p($publicationId); ?>&Issue=<?php  p($issue->getIssueId()); ?>&Section=<?php p($section->getSectionId()); ?>&Language=<?php p($section->getLanguageId()); ?>"><?php p(htmlspecialchars($section->getName())); ?></a></td>
							</tr>
							<?php
						} // foreach ($sections
					}
				} // foreach ($issues
			} // if (isset($issues[$publicationId]))
		} // foreach ($publications
		?>		
		</table>
	</TD>
	
	<TD VALIGN="TOP" align="right">
		<?php  if ($What) { ?>

		<TABLE BORDER="0" CELLSPACING="1" CELLPADDING="3">
		<TR>
			<TD colspan="3" style="font-weight: bold; font-size: 10pt; padding-top: 0px">
				<?php  putGS("Your articles"); ?>
			</TD>
		</TR>
		<TR class="table_list_header">
			<TD ALIGN="LEFT" VALIGN="TOP" width="450px"><B><?php  putGS("Name<BR><SMALL>(click to edit article)</SMALL>"); ?></B></TD>
			<TD ALIGN="LEFT" VALIGN="TOP" WIDTH="100px" ><B><?php  putGS("Language"); ?></B></TD>
			<TD ALIGN="LEFT" VALIGN="TOP" WIDTH="100px" ><B><?php  putGS("Status"); ?></B></TD>
		</TR>

		<?php 
		$color = 0;
		foreach ($YourArticles as $YourArticle) {
			$section =& $YourArticle->getSection();
			$language =& new Language($YourArticle->getLanguageId());
			 ?>
		<TR <?php if ($color) { $color=0; ?>class="list_row_even"<?php  } else { $color=1; ?>class="list_row_odd"<?php  } ?>>
			<TD width="450px">
				<?php 
				if ($User->hasPermission('ChangeArticle') || ($YourArticle->getPublished() == 'N')) {
					echo CampsiteInterface::ArticleLink($YourArticle, $section->getLanguageId(), "edit.php"); 
				}
				p(htmlspecialchars($YourArticle->getTitle()));
				if ($User->hasPermission('ChangeArticle') || ($YourArticle->getPublished() == 'N')) {
					echo '</a>';
				}
				?>
			</TD>
			
			<TD>
				<?php p(htmlspecialchars($language->getName())); ?>
			</TD>
			
			<TD>
				<?php 
				$changeStatusLink = CampsiteInterface::ArticleLink($YourArticle, $section->getLanguageId(), "status.php", $REQUEST_URI);
				if ($YourArticle->getPublished() == "Y") { 
					if ($User->hasPermission('Publish')) {
						echo $changeStatusLink;
					}
					putGS('Published'); 
					if ($User->hasPermission('Publish')) {
						echo '</a>';
					}
				} 
				elseif ($YourArticle->getPublished() == 'S') { 
					if ($User->hasPermission('Publish')) {
						echo $changeStatusLink; 
					}
					putGS('Submitted'); 
					if ($User->hasPermission('Publish')) {
						echo '</a>';
					}
				} 
				elseif ($YourArticle->getPublished() == "N") { 
					echo $changeStatusLink;
					putGS('New'); 
					echo '</A>';
				} 
				?>
			</TD>
		</TR>
		<?php 
		} // for
    	?>
	
    	<TR>
    		<TD COLSPAN="2" NOWRAP>
				<?php  
				if ($ArtOffs<=0) { 
					p(htmlspecialchars("<< ")); 
					putGS('Previous'); 
				} 
				else { ?>
					<B><A HREF="home.php?ArtOffs=<?php print ($ArtOffs - $NumDisplayArticles); ?>&What=1"><?php p(htmlspecialchars("<< ")); putGS('Previous'); ?></A></B>
					<?php  
				} 
				if ( ($ArtOffs + $NumDisplayArticles) >= $NumYourArticles ) { 
					?>
					| <?php putGS('Next'); p(htmlspecialchars(" >>"));
				} 
				else { ?>
					| <B><A HREF="home.php?ArtOffs=<?php print ($ArtOffs + $NumDisplayArticles); ?>&What=1"><?php putGS('Next'); p(htmlspecialchars(" >>")); ?></A></B>
					<?php  
				} 
				?>	
			</TD>
		</TR>
		</TABLE>
		<?php  
		} // if ($What)
		else { 
			// Submitted articles
			?>
		<TABLE BORDER="0" CELLSPACING="1" CELLPADDING="3">
		<tr>
			<td valign="top" colspan="2" style="font-weight: bold; font-size: 10pt; padding-top: 0px;">
				<?php putGS("Submitted articles"); ?>
			</td>
		</tr>
		
		<TR class="table_list_header">
			<TD ALIGN="LEFT" VALIGN="TOP" width="550px"><B><?php  putGS("Name<BR><SMALL>(click to edit article)</SMALL>"); ?></B></TD>
			<TD ALIGN="LEFT" VALIGN="TOP" width="100px"><B><?php  putGS("Language"); ?></B></TD>
		</TR>
		<?php 
	    $color=0;
		foreach ($SubmittedArticles as $SubmittedArticle) {
			$section =& $SubmittedArticle->getSection();
			$language =& new Language($SubmittedArticle->getLanguageId());
			?>	
		<TR <?php if ($color) { $color=0; ?>class="list_row_even"<?php  } else { $color=1; ?>class="list_row_odd"<?php  } ?>>
			<TD width="550px">
			<?php echo CampsiteInterface::ArticleLink($SubmittedArticle, $section->getLanguageId(), "edit.php"); ?>
			<?php p(htmlspecialchars($SubmittedArticle->getTitle())); ?>
			</A>
			</TD>
			
			<TD>
			<?php p(htmlspecialchars($language->getName()));?>
			</TD>
		</TR>
		<?php 
		} // for ($SubmittedArticles ...)
		?>	

		<TR>
			<TD COLSPAN="2" NOWRAP>
			<?php 
			if ($NArtOffs <= 0) { 
				p(htmlspecialchars("<< "));  
				putGS('Previous'); 
			} 
			else { ?>
				<B><A HREF="home.php?NArtOffs=<?php print ($NArtOffs - $NumDisplayArticles); ?>&What=0"><?php p(htmlspecialchars("<< ")); putGS('Previous'); ?></A></B>
				<?php  
    		}
    		if (($NArtOffs + $NumDisplayArticles) >= $NumSubmittedArticles) { ?>
    	 		| <?php  putGS('Next'); p(htmlspecialchars(" >>")); 
    		} 
    		else { ?>
    			| <B><A HREF="home.php?NArtOffs=<?php  print ($NArtOffs + $NumDisplayArticles); ?>&What=0"><?php putGS('Next'); p(htmlspecialchars(" >>")); ?></A></B>
				<?php  
    		} 
    		?>	
			</TD>
		</TR>
		</TABLE>
		<?php  
		} // if (!$What)
		?>
		<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="1">
		<TR>
		<?php 
    	if ($What) {
			if ($User->hasPermission("ChangeArticle")) { ?>	
			<TD>
				<TABLE>
				<TR>
					<TD ALIGN="RIGHT"><A HREF="home.php?What=0"><IMG SRC="/<?php echo $ADMIN; ?>/img/tol.gif" BORDER="0" ALT="<?php  putGS("Submitted articles"); ?>"></A></TD>
					<TD NOWRAP><A HREF="home.php?What=0"><?php  putGS("Submitted articles"); ?></A></TD>
				</TR>
				</TABLE>
			</TD>
			<?php  
			} 
    	}    
 		else { ?>	
 			<TD>
 				<TABLE>
				<TR>
					<TD ALIGN="RIGHT"><A HREF="home.php?What=1"><IMG SRC="/<?php echo $ADMIN; ?>/img/tol.gif" BORDER="0" ALT="<?php  putGS("Your articles"); ?>"></A></TD>
					<TD NOWRAP><A HREF="home.php?What=1"><?php  putGS("Your articles"); ?></A></TD>
				</TR>
				</TABLE>
			</TD>
			<?php  
 		} 
 		?>
		</TR>
		</TABLE>
    </TD>
</TR>
</TABLE>
<?php CampsiteInterface::CopyrightNotice(); ?>