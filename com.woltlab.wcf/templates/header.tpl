{include file='documentHeader'}

<head>
	<title>{if $pageTitle|isset}{@$pageTitle} - {/if}{PAGE_TITLE|language}</title>
	
	{include file='headInclude'}
	
	{if !$headContent|empty}
		{@$headContent}
	{/if}
</head>

<body id="tpl_{$templateNameApplication}_{$templateName}" data-template="{$templateName}" data-application="{$templateNameApplication}">

<a id="top"></a>

<div id="pageContainer" class="pageContainer">
	{event name='beforePageHeader'}
	
	{include file='pageHeader'}
	
	{event name='afterPageHeader'}
	
	{hascontent}
		<div class="boxesHeaderBoxes">
			<div class="layoutBoundary">
				<div class="boxContainer">
					{content}
						{foreach from=$__wcf->getBoxHandler()->getBoxes('headerBoxes') item=box}
							{@$box->render()}
						{/foreach}
					{/content}
				</div>	
			</div>
		</div>
	{/hascontent}
	
	{include file='pageNavbarTop'}
	
	{hascontent}
		<div class="boxesTop">
			<div class="boxContainer">
				{content}
					{if !$boxesTop|empty}
						{@$boxesTop}
					{/if}
				
					{foreach from=$__wcf->getBoxHandler()->getBoxes('top') item=box}
						{@$box->render()}
					{/foreach}
				{/content}
			</div>	
		</div>
	{/hascontent}
	
	<section id="main" class="main" role="main">
		<div class="layoutBoundary">
			{hascontent}
				<aside class="sidebar boxesSidebarLeft">
					<div class="boxContainer">
						{content}
							{if MODULE_WCF_AD && $__disableAds|empty}{@$__wcf->getAdHandler()->getAds('com.woltlab.wcf.sidebar.top')}{/if}
							
							{event name='boxesSidebarLeftTop'}
							
							{* WCF2.1 Fallback *}
							{if !$sidebar|empty}
								{if !$sidebarOrientation|isset || $sidebarOrientation == 'left'}
									{@$sidebar}
								{/if}	
							{/if}
							
							{if !$sidebarLeft|empty}
								{@$sidebarLeft}
							{/if}
							
							{foreach from=$__wcf->getBoxHandler()->getBoxes('sidebarLeft') item=box}
								{@$box->render()}
							{/foreach}
				
							{event name='boxesSidebarLeftBottom'}
				
							{if MODULE_WCF_AD && $__disableAds|empty}{@$__wcf->getAdHandler()->getAds('com.woltlab.wcf.sidebar.bottom')}{/if}
						{/content}
					</div>	
				</aside>
			{/hascontent}
			
			<div id="content" class="content">
				{if MODULE_WCF_AD && $__disableAds|empty}{@$__wcf->getAdHandler()->getAds('com.woltlab.wcf.header.content')}{/if}
				
				{if !$contentHeader|empty}
					{@$contentHeader}
				{elseif !$contentTitle|empty}
					<header class="contentHeader">
						<div class="contentHeaderTitle">
							<h1 class="contentTitle">{@$contentTitle}</h1>
							{if !$contentDescription|empty}<p class="contentHeaderDescription">{@$contentDescription}</p>{/if}
						</div>
						
						{hascontent}
							<nav class="contentHeaderNavigation">
								<ul>
									{content}{event name='contentHeaderNavigation'}{/content}
								</ul>
							</nav>
						{/hascontent}
					</header>
				{/if}
				
				{include file='userNotice'}
				
				{hascontent}
					<div class="boxesContentTop">
						<div class="boxContainer">
							{content}
								{foreach from=$__wcf->getBoxHandler()->getBoxes('contentTop') item=box}
									{@$box->render()}
								{/foreach}
							{/content}
						</div>	
					</div>
				{/hascontent}
				
				{event name='contents'}
