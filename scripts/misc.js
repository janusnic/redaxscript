/**
 * @tableofcontents
 *
 * 1. forward notification
 * 2. key shortcut
 * 3. startup
 *
 * @since 2.0.0
 *
 * @package Redaxscript
 * @author Henry Ruhs
 */

(function ($)
{
	'use strict';

	/* @section 1. forward notification */

	$.fn.forwardNotification = function (options)
	{
		/* extend options */

		if (rs.plugins.forwardNotification.options !== options)
		{
			options = $.extend({}, rs.plugins.forwardNotification.options, options || {});
		}

		/* return this */

		return this.each(function ()
		{
			var link = $(this),
				url = link.attr('href');

			/* timeout enhanced forward */

			if (typeof url === 'string')
			{
				setTimeout(function ()
				{
					if (url.substr(0, 2) === '//' || url.substr(0, 7) === 'http://' || url.substr(0, 8) === 'https://')
					{
						window.location = url;
					}
					else
					{
						window.location = rs.baseURL + url;
					}
				}, options.duration);
			}
		});
	};

	/* @section 2. key shortcut */

	$.fn.keyShortcut = function (options)
	{
		/* extend options */

		if (rs.plugins.keyShortcut.options !== options)
		{
			options = $.extend({}, rs.plugins.keyShortcut.options, options || {});
		}

		/* return this */

		return this.each(function ()
		{
			/* listen for keydown */

			$(this).on('keydown', function (event)
			{
				var adminDock = $(options.element.adminDock),
					adminPanel = $(options.element.adminPanel),
					buttonSubmit = $(options.element.buttonSubmit),
					buttonOk = $(options.element.buttonOk),
					buttonCancel = $(options.element.buttonCancel);

				if (event.ctrlKey && event.altKey)
				{
					/* trigger cancel action */

					if (event.which === options.keyCode.cancel)
					{
						buttonCancel.click();
					}

					/* toggle admin dock */

					else if (event.which === options.keyCode.dock)
					{
						adminDock.toggle();
					}

					/* login and logout */

					else if (event.which === options.keyCode.log)
					{
						if (rs.registry.LOGGED_IN === rs.registry.TOKEN)
						{
							window.location = rs.baseURL + rs.registry.REWRITE_ROUTE + rs.plugins.keyShortcut.routes.logout;
						}
						else
						{
							window.location = rs.baseURL + rs.registry.REWRITE_ROUTE + rs.plugins.keyShortcut.routes.login;
						}
					}

					/* trigger ok action */

					else if (event.which === options.keyCode.ok)
					{
						buttonOk.click();
					}

					/* toggle admin panel */

					else if (event.which === options.keyCode.toggle)
					{
						adminPanel.toggle();
					}

					/* trigger submit action */

					else if (event.which === options.keyCode.submit)
					{
						buttonSubmit.click();
					}
				}
			});
		});
	};

	/* @section 3. startup */

	$(function ()
	{
		if (rs.plugins.keyShortcut.startup)
		{
			$(rs.plugins.keyShortcut.selector).keyShortcut(rs.plugins.keyShortcut.options);
		}
		if (rs.plugins.forwardNotification.startup)
		{
			$(rs.plugins.forwardNotification.selector).forwardNotification(rs.plugins.forwardNotification.options);
		}
	});
})(window.jQuery || window.Zepto);