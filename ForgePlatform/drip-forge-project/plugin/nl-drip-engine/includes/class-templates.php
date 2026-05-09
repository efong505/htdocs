<?php
if (!defined('ABSPATH')) exit;

/**
 * Pre-built sequence templates for one-click import.
 */
class NLDE_Templates {

	public static function get_all() {
		return [
			'welcome-series' => [
				'name'        => 'Welcome Series',
				'description' => 'Greet new subscribers, introduce your brand, and set expectations.',
				'category'    => 'Onboarding',
				'emails'      => [
					['position' => 0, 'delay_days' => 0, 'subject' => 'Welcome to {site_name}!', 'body' => "Hi {first_name},\n\nThanks for subscribing! We're glad to have you.\n\nHere's what you can expect from us:\n• Helpful tips and resources delivered to your inbox\n• Exclusive content you won't find anywhere else\n• No spam — ever. We respect your time.\n\nIf you have any questions, just reply to this email.\n\nCheers,\n{site_name}"],
					['position' => 1, 'delay_days' => 2, 'subject' => 'Our most popular resource', 'body' => "Hi {first_name},\n\nWanted to share our most popular resource with you — it's helped hundreds of people just like you.\n\n[Link to your best content here]\n\nLet us know what you think!\n\n{site_name}"],
					['position' => 2, 'delay_days' => 5, 'subject' => 'Quick question for you', 'body' => "Hi {first_name},\n\nWe'd love to know — what's your biggest challenge right now?\n\nJust hit reply and let us know. We read every response and it helps us create better content for you.\n\nTalk soon,\n{site_name}"],
				],
			],
			'lead-magnet-delivery' => [
				'name'        => 'Lead Magnet Delivery',
				'description' => 'Deliver a free resource, then nurture the lead toward your offer.',
				'category'    => 'Lead Generation',
				'emails'      => [
					['position' => 0, 'delay_days' => 0, 'subject' => 'Your free download is ready!', 'body' => "Hi {first_name},\n\nThanks for requesting our free resource! Here's your download link:\n\n{download_link}\n\nThis guide covers everything you need to get started. Take your time with it — there's a lot of good stuff in there.\n\nCheers,\n{site_name}"],
					['position' => 1, 'delay_days' => 2, 'subject' => 'Did you get a chance to check it out?', 'body' => "Hi {first_name},\n\nJust checking in — did you get a chance to look at the resource we sent?\n\nHere are the top 3 things people find most useful:\n\n1. [Key takeaway #1]\n2. [Key takeaway #2]\n3. [Key takeaway #3]\n\nIf you have any questions, just reply to this email.\n\n{site_name}"],
					['position' => 2, 'delay_days' => 5, 'subject' => 'The next step', 'body' => "Hi {first_name},\n\nNow that you've had the free resource, here's the natural next step:\n\n[Introduce your paid product/service here]\n\nThis is perfect for you if:\n• [Benefit 1]\n• [Benefit 2]\n• [Benefit 3]\n\n[Link to your offer]\n\nNo pressure — just wanted you to know it's there when you're ready.\n\n{site_name}"],
					['position' => 3, 'delay_days' => 8, 'subject' => 'People are asking about this', 'body' => "Hi {first_name},\n\nWe've been getting a lot of questions about [topic related to your offer], so here's a quick FAQ:\n\nQ: [Common question 1]\nA: [Answer]\n\nQ: [Common question 2]\nA: [Answer]\n\nQ: [Common question 3]\nA: [Answer]\n\nStill have questions? Just reply — we're here to help.\n\n{site_name}"],
				],
			],
			'customer-onboarding' => [
				'name'        => 'Customer Onboarding',
				'description' => 'Guide new customers through setup and first steps after purchase.',
				'category'    => 'Onboarding',
				'emails'      => [
					['position' => 0, 'delay_days' => 0, 'subject' => 'You\'re in! Here\'s how to get started', 'body' => "Hi {first_name},\n\nWelcome aboard! Here's how to get started in 3 simple steps:\n\n1. [First setup step]\n2. [Second setup step]\n3. [Third setup step]\n\nIf you run into any issues, our support team is here to help.\n\n{site_name}"],
					['position' => 1, 'delay_days' => 3, 'subject' => 'Pro tip: Get the most out of your purchase', 'body' => "Hi {first_name},\n\nHere's a tip most people miss:\n\n[Share a power-user tip or hidden feature]\n\nThis alone can save you hours. Give it a try and let us know how it goes!\n\n{site_name}"],
					['position' => 2, 'delay_days' => 7, 'subject' => 'How\'s everything going?', 'body' => "Hi {first_name},\n\nYou've had a week with [product name] — how's it going?\n\nWe'd love to hear your feedback. What's working well? What could be better?\n\nJust hit reply — we read every response.\n\n{site_name}"],
				],
			],
			're-engagement' => [
				'name'        => 'Re-engagement Campaign',
				'description' => 'Win back inactive subscribers before removing them from your list.',
				'category'    => 'Retention',
				'emails'      => [
					['position' => 0, 'delay_days' => 0, 'subject' => 'We miss you, {first_name}!', 'body' => "Hi {first_name},\n\nWe noticed you haven't opened our emails in a while. No hard feelings — we get it, inboxes are busy.\n\nBut we've been working on some great new content and didn't want you to miss out:\n\n[Link to your best recent content]\n\nStill interested? No action needed — just keep reading.\n\nWant to unsubscribe? No problem: {unsubscribe_link}\n\n{site_name}"],
					['position' => 1, 'delay_days' => 4, 'subject' => 'Last chance to stay on the list', 'body' => "Hi {first_name},\n\nThis is a quick heads-up: we're cleaning up our email list, and since we haven't heard from you in a while, we want to make sure you still want to receive our emails.\n\nIf you want to keep getting our content, just click here:\n{site_url}\n\nIf we don't hear from you, we'll remove you from the list in a few days. No hard feelings either way.\n\n{site_name}"],
				],
			],
			'product-launch' => [
				'name'        => 'Product Launch Sequence',
				'description' => 'Build anticipation, launch your product, and follow up with buyers.',
				'category'    => 'Sales',
				'emails'      => [
					['position' => 0, 'delay_days' => 0, 'subject' => 'Something new is coming...', 'body' => "Hi {first_name},\n\nWe've been working on something behind the scenes and we're almost ready to share it with you.\n\nHere's a hint: [Tease the product/feature]\n\nStay tuned — more details coming in a few days.\n\n{site_name}"],
					['position' => 1, 'delay_days' => 3, 'subject' => 'It\'s here: Introducing [Product Name]', 'body' => "Hi {first_name},\n\nThe wait is over! We're excited to introduce [Product Name].\n\n[What it does — 2-3 sentences]\n\nHere's what's included:\n• [Feature 1]\n• [Feature 2]\n• [Feature 3]\n\n[Link to product page]\n\nLaunch pricing is available for a limited time.\n\n{site_name}"],
					['position' => 2, 'delay_days' => 5, 'subject' => 'What people are saying about [Product Name]', 'body' => "Hi {first_name},\n\nThe response to [Product Name] has been incredible. Here's what early users are saying:\n\n\"[Testimonial 1]\" — [Name]\n\"[Testimonial 2]\" — [Name]\n\n[Link to product page]\n\nLaunch pricing ends soon.\n\n{site_name}"],
					['position' => 3, 'delay_days' => 7, 'subject' => 'Last call: Launch pricing ends tonight', 'body' => "Hi {first_name},\n\nJust a heads-up — launch pricing for [Product Name] ends tonight at midnight.\n\nAfter that, the price goes up to [regular price].\n\nIf you've been on the fence, now's the time:\n[Link to product page]\n\n{site_name}"],
				],
			],
			'educational-series' => [
				'name'        => 'Educational Email Course',
				'description' => 'Teach a topic over several days to build authority and trust.',
				'category'    => 'Education',
				'emails'      => [
					['position' => 0, 'delay_days' => 0, 'subject' => 'Lesson 1: [Topic Foundation]', 'body' => "Hi {first_name},\n\nWelcome to our free email course! Over the next 5 days, you'll learn [what they'll learn].\n\nLesson 1: [Topic Foundation]\n\n[Teach the first concept — 3-5 paragraphs]\n\nKey takeaway: [One sentence summary]\n\nSee you tomorrow for Lesson 2!\n\n{site_name}"],
					['position' => 1, 'delay_days' => 1, 'subject' => 'Lesson 2: [Building on the Basics]', 'body' => "Hi {first_name},\n\nLesson 2: [Building on the Basics]\n\n[Teach the second concept — 3-5 paragraphs]\n\nAction step: [Give them something to do]\n\nSee you tomorrow!\n\n{site_name}"],
					['position' => 2, 'delay_days' => 2, 'subject' => 'Lesson 3: [The Common Mistake]', 'body' => "Hi {first_name},\n\nLesson 3: [The Common Mistake]\n\nMost people get this wrong: [Explain the mistake]\n\nHere's what to do instead: [Teach the correct approach]\n\nTomorrow we'll cover the most important lesson in this series.\n\n{site_name}"],
					['position' => 3, 'delay_days' => 3, 'subject' => 'Lesson 4: [The Game Changer]', 'body' => "Hi {first_name},\n\nLesson 4: [The Game Changer]\n\nThis is the lesson that changes everything: [Teach the key insight]\n\n[Explain why this matters — 3-5 paragraphs]\n\nOne more lesson tomorrow — and it ties everything together.\n\n{site_name}"],
					['position' => 4, 'delay_days' => 4, 'subject' => 'Lesson 5: [Putting It All Together]', 'body' => "Hi {first_name},\n\nFinal lesson! Let's put it all together.\n\n[Summarize lessons 1-4 and show how they connect]\n\nYour next step: [Clear call to action]\n\nWant to go deeper? [Link to your product/service]\n\nThanks for completing the course!\n\n{site_name}"],
				],
			],
		];
	}

	public static function get($slug) {
		$all = self::get_all();
		return $all[$slug] ?? null;
	}

	public static function get_categories() {
		$templates = self::get_all();
		$categories = [];
		foreach ($templates as $t) {
			$categories[$t['category']] = true;
		}
		return array_keys($categories);
	}

	public static function import($slug) {
		$template = self::get($slug);
		if (!$template) {
			return new WP_Error('nlde_invalid_template', 'Template not found.');
		}

		$seq_id = NLDE_Drip_Sequence::create([
			'name'        => $template['name'],
			'description' => $template['description'],
			'status'      => 'draft',
		]);

		if (!$seq_id) {
			return new WP_Error('nlde_create_failed', 'Failed to create sequence.');
		}

		foreach ($template['emails'] as $email) {
			NLDE_Drip_Sequence::add_email([
				'sequence_id' => $seq_id,
				'position'    => $email['position'],
				'delay_days'  => $email['delay_days'],
				'subject'     => $email['subject'],
				'body'        => $email['body'],
			]);
		}

		return $seq_id;
	}
}
