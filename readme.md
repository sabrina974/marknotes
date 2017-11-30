[![Marknotes Logo](https://raw.githubusercontent.com/cavo789/marknotes/master/src/assets/images/marknotes.png)](https://www.marknotes.fr)

>If you like marknotes, please give him a :star: and fork it.

![License MIT](https://www.marknotes.fr/assets/images/license.png)

## Table of Contents

1. **[Description](#description)**
	1.1. **[What is marknotes ?](#what-is-marknotes)**
	1.2. **[Notes are yours !](#notes-are-yours)**
	1.3. **[Background](#background)**
2. **[:game_die: Demo](#demo)**
3. **[How to install](#how-to-install-or-update)**
	3.1. **[:smile: Easy way](#easy-way)**
	3.2. **[Hard way](#hard-way)**
4. **[:book: Documentation](#documentation)**
5. **[:hammer: Configuration](#configuration)**
	5.1. **[Plugins](#plugins)**
6. **[:man: Author](#author)**
7. **[Follow us](#follow-us)**
8. **[License](#license)**

## 1. Description

### 1.1 What is marknotes ?

Marknotes is a PHP application that you can self-hosted and who will help you to manage your "notes" : documentations, meeting minutes, user guides, ebooks, emails, ... i.e. everything you put in it.

Notes are written in pure [Markdown](https://daringfireball.net/projects/markdown/syntax) : this is a plain text language with only a few codes (like `#` or `*`) for text formatting. Yes !!!  You'll write your notes with a very stupid text editor (`Notepad` on Windows will do the work) (note : marknotes provide an online editor) and save them as a flatfile on your server.

Marknotes will display yours notes in a folder approach and files will be displayed in a nice HTML5 output with a lot of extra features : export the note as a .docx, .epub, .odt, .pdf, .txt, ... file or display it like a slideshow (support of [Reveal.Js](https://github.com/hakimel/reveal.js) and [Remark](http://gnab.github.com/remark) included)

Marknotes is fully Open Source and is using just *stupid* text files (markdown ones). It's really easy to create files, edit them in any text editor, ... and due to the file format, the integration with any tools and existing process is easy.

### 1.2 Notes are yours !

Want to move to an another application and leave marknotes ? No problem ! Notes are flatfiles and written in a text format and use markdown which is a standard.

Even if I'd be really sad to see you leave, you'll certainly not have any problem to do it. Just move your .md files and that's all.

### 1.3 Background

During years, I've used Evernotes™ to manage my notes (can be documentation, user guide, ebooks, billing, ... i.e. everything I need to keep in one central place and being able to retrieve them easily).

In 2016, Evernote has introduced more restriction with the Free version and by the end of the year, has stated that somes employees will have access to **our** notes for administration tasks.

Then I've said "No !" : no, even if the tool was easy and free, no, I didn't want to an human can get access to my knowledge base, I wish to be able to better manage who / when / why and, on top of this, somes features were missing for me : easily display notes as an HTML page, as a slideshow, convert them in f.i. a Word document, and so on.

**[⬆ back to top](#table-of-contents)**

---

## 2. :game_die: Demo site

The demo is available on [https://www.marknotes.fr](https://www.marknotes.fr). Please take a look.

marknotes is available in French and in English. The configuration on the demo site is done for french speaking people.

---

**[⬆ back to top](#table-of-contents)**

## 3. How to install or update ?

>If you already have a version of marknotes, if this is the old version 1; I recommand to remove all files **except the /docs folder** (don't loose your notes!). Otherwise, let files there and overwrite them by using the easy or hard way here below

### 3.1 :smile: Easy way

The simply way to install marknotes is by downloading his [installation script](https://github.com/cavo789/marknotes_install) which is available in a separate github repository.

Download `install.php` from there, save the file in the folder where you wish to install a copy of marknotes and just run the script (f.i. `http://localhost/marknotes/install.php`).

The script will get the latest version of marknotes, download his zip from GitHub and save it on your server, unzip the file and prepare the site. After two seconds, you should have a running site.

### 3.2 Hard way

You can of course download a copy of this repo by getting the [ZIP version](https://github.com/cavo789/marknotes/archive/master.zip) or making a clone (`git clone https://github.com/cavo789/marknotes`).

You'll find the source files in the `/src` folder. Take a copy of all these files and put them in your marknotes folder.

---

**[⬆ back to top](#table-of-contents)**

## 4. :book: Documentation

Marknotes's documentation is available on the marknotes demo site : [https://www.marknotes.fr](https://www.marknotes.fr). See the marknotes entry.

You can also find the previous documentation (for the version 1 of the tool) on the wiki here : [https://github.com/cavo789/marknotes/wiki](https://github.com/cavo789/marknotes/wiki)

---

**[⬆ back to top](#table-of-contents)**

## 5. :hammer: Configuration

Marknotes will fit yours needs, without any exceptions : you can change everything by overwriting the [settings.json](https://github.com/cavo789/marknotes/blob/master/src/settings.json.dist) file.

Every settings are indeed stored as a value-key entry in the [settings.json](https://github.com/cavo789/marknotes/blob/master/src/settings.json.dist) file.

The master file is called `settings.json.dist` and is stored in the root folder of marknotes. That file is part of the repository so ... don't change it (because on the next update of marknotes, settings.json.dist will be replaced by a fresh copy).

**Don't update it but copy it !** Duplicate the `settings.json.dist` file and name the new one `settings.json`.

Marknotes will always first read `settings.json.dist` and, only then, if a file called `settings.json` exists, will load that second file. In other words : marknotes will read his default settings and will load yours then. So, yours will overwrite the default one.

In fact, the `settings.json` shouldn't be a full copy of `settings.json.dist` but, it's the best approach, should only contains the updated value.

For instance, if you wish to change the value of the `site_name` property, your settings.json should (can) only contains this :

```json
{
	"site_name": "My awesome documentation site"
}
```

And that's all since, like explained here above, marknotes will first read `settings.json.dist` (he'll get all default settings) and you only need to say : please overwrite the `site_name` setting and take mine.

The example below show how you can modify more than one setting : here, you'll set your regional settings to fr-FR and you'll also mention that the folder contains notes isn't the default `docs` folder but, on your site, is called `my_notes`.

You can use this approach and overwrite every single setting of marknotes.

```json
{
	"regional": {
		"locale": "fr-FR",
		"language": "fr"
	},
	"folder": "my_notes",
	"site_name": "My awesome documentation site"
}
```

### Hierarchy approach

**And more**

The file settings.json can be placed in your root folder (then will apply to every notes) but you can also place this file in a given folder.

Let's imagine this structure :

```text
	/docs
		/github
			/repo_1
			/repo_2
		/marknotes
			/en
				/ ... (a lot of subfolders)
			/fr
				/ ... (a lot of subfolders)
			/nl
```

You've a lot of subfolders in `/docs` and, for marknotes, you've three subfolders, one by language (English, French or Dutch).

In the folder `/docs/marknotes/en`, you can decide to use international settings like f.i.

```json
{
	"regional": {
		"locale": "en-GB",
		"language": "en"
	},
	"site_name": "My awesome documentation site"
}
```

while, in `/docs/marknotes/fr`, you'll have

```json
{
	"regional": {
		"locale": "fr-FR",
		"language": "fr"
	},
	"site_name": "Mon site de documentation"
}
```

The `settings.json` file **can be placed in any folder of your site** and you can have more than one `settings.json` file, marknotes will always respect the `hierarchy` (just like Apache does with a .htaccess file f.i.) :

1. `/settings.json.dist`
2. `/settings.json`
3. `/docs/settings.json`
4. `/docs/marknotes/settings.json`
5. `/docs/marknotes/fr/settings.json`

And one more step : you can name the file `my_note.json` and store it in the same folder of  `my_note.md` so the file will only apply for that specific note and not every note of the folder.

---

**[⬆ back to top](#table-of-contents)**

### 5.1 Plugins

There are dozens of plugins for marknotes, you can choose to enable them or not just by updating the `settings.json`. The example below disable the plugin called "font-awesome" (this plugin will replace f.i. `:fa-star:` by a star (thanks the integration of [Font-awesome](https://github.com/FortAwesome/Font-Awesome))

```json
{
	"plugins": {
		"content": {
			"html": {
				"font-awesome": {
					"enabled": 0
				}
			}
		}
	}
}
```

There are plugins for the generation of a table of content, table of "todos", for image gallery generation, to get a list of files, for displaying the last update date/time of the note, for encryption support and much more.

Each plugin can be enabled / disabled and configured directly in the `settings.json` file; for the entire site or for a given folder.

---

**[⬆ back to top](#table-of-contents)**

## 6. :man: Author

marknotes has been created and is maintained by [Christophe Avonture](https://github.com/cavo789) | [https://www.aesecure.com](https://www.aesecure.com)

## 7. Follow us

Follow us on [Facebook](https://www.facebook.com/marknotes789/) to stay up-to-date

## 8. License

[MIT](https://github.com/cavo789/marknotes/blob/master/LICENSE)

**[⬆ back to top](#table-of-contents)**

>If you like marknotes, please give him a :star: and fork it.