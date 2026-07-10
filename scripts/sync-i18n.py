#!/usr/bin/env python3
"""Sync i18n lang files: panel-en/no from panel-uk; public uk/en/no."""
import re
import sys
from pathlib import Path

LANG = Path(__file__).resolve().parent.parent / 'lang'

LINE_RE = re.compile(r"^\s+'([a-zA-Z0-9_]+)'\s*=>\s*(.+?),?\s*$")


def parse_php_lang(path: Path) -> dict[str, str]:
    data: dict[str, str] = {}
    for line in path.read_text(encoding='utf-8').splitlines():
        m = LINE_RE.match(line)
        if m:
            key, val = m.group(1), m.group(2).strip()
            if val.endswith(','):
                val = val[:-1].strip()
            data[key] = val
    return data


def php_escape(s: str) -> str:
    return "'" + s.replace("\\", "\\\\").replace("'", "\\'") + "'"


def write_php_lang(path: Path, data: dict[str, str], ref_order: list[str]) -> None:
    lines = ['<?php', 'return [']
    seen = set()
    for key in ref_order:
        if key in data and key not in seen:
            lines.append(f"    '{key}' => {data[key]},")
            seen.add(key)
    for key in sorted(data.keys()):
        if key not in seen:
            lines.append(f"    '{key}' => {data[key]},")
    lines.append('];')
    path.write_text('\n'.join(lines) + '\n', encoding='utf-8')


# English translations for keys missing from panel-en (from panel-uk Ukrainian)
PANEL_EN: dict[str, str] = {
    'btn_add': 'Add',
    'checkout_card': 'Card number',
    'checkout_demo': 'Demo payment — click to activate without a real charge.',
    'checkout_domain': 'Domain',
    'checkout_pay': 'Pay and activate',
    'checkout_plan': 'Plan',
    'checkout_title': 'Plan payment',
    'cron_added': 'Add cron job',
    'db_remote_access': 'Remote MySQL',
    'db_remote_off': 'Disable remote access',
    'db_remote_on': 'Enable remote access',
    'domain_available': 'Available',
    'domain_checking': 'Checking registry…',
    'domain_invalid': 'Enter a valid domain (e.g. mysite.lt)',
    'domain_lookup_error': 'Could not check the registry. Please try again.',
    'domain_register_cta': 'Hosting + domain',
    'domain_search_btn': 'Check',
    'domain_search_title': 'Find a domain',
    'domain_taken': 'Taken',
    'file_upload': 'Upload file',
    'file_uploaded': 'File uploaded',
    'ftp_pass_reset': 'Generate password',
    'git_deploy': 'Deploy from Git',
    'git_deployed': 'Deploy queued',
    'landing_block_app_cta': 'Download app',
    'landing_block_app_cta_desc': 'App Store and Google Play buttons.',
    'landing_block_buttons': 'Buttons',
    'landing_block_buttons_desc': 'Row of buttons in different styles (primary, outline, ghost).',
    'landing_block_callout': 'Callout',
    'landing_block_callout_desc': 'Highlighted message with accent and button.',
    'landing_block_cards': 'Cards',
    'landing_block_cards_desc': 'Flexible cards with icon and optional button.',
    'landing_block_comparison': 'Comparison',
    'landing_block_comparison_desc': 'Table comparing two options.',
    'landing_block_contact_bar': 'Quick contact',
    'landing_block_contact_bar_desc': 'Phone, email, WhatsApp in a bar.',
    'landing_block_icon_list': 'Icon list',
    'landing_block_icon_list_desc': 'Benefits or steps with icons.',
    'landing_block_media_text': 'Image + text',
    'landing_block_media_text_desc': 'Photo beside text and CTA.',
    'landing_block_menu': 'Menu / price list',
    'landing_block_menu_desc': 'Dishes or services with prices.',
    'landing_block_social': 'Social media',
    'landing_block_social_desc': 'Links to Facebook, Instagram, LinkedIn, etc.',
    'landing_block_stats_bar': 'Stats bar',
    'landing_block_stats_bar_desc': 'Horizontal numbers (clients, years, rating).',
    'landing_block_trust': 'Trust badges',
    'landing_block_trust_desc': 'Security, certificates, guarantees.',
    'landing_tpl_accounting': 'Accounting',
    'landing_tpl_accounting_desc': 'Bookkeeping, reporting, consulting',
    'landing_tpl_auto': 'Auto repair',
    'landing_tpl_auto_desc': 'Repair, diagnostics, tires',
    'landing_tpl_bakery': 'Bakery',
    'landing_tpl_bakery_desc': 'Pastries, cakes, menu',
    'landing_tpl_barber': 'Barbershop',
    'landing_tpl_barber_desc': 'Haircuts, beard, price list',
    'landing_tpl_beauty': 'Beauty salon',
    'landing_tpl_beauty_desc': 'Services, prices, booking',
    'landing_tpl_cleaning': 'Cleaning',
    'landing_tpl_cleaning_desc': 'Home and office cleaning',
    'landing_tpl_coaching': 'Coaching',
    'landing_tpl_coaching_desc': 'Personal development, mentoring',
    'landing_tpl_construction': 'Construction',
    'landing_tpl_construction_desc': 'Renovation, projects, estimates',
    'landing_tpl_dentist': 'Dentistry',
    'landing_tpl_dentist_desc': 'Clinic, services, prices, booking',
    'landing_tpl_education': 'Education / courses',
    'landing_tpl_education_desc': 'Programs, instructors, enrollment',
    'landing_tpl_fitness': 'Fitness club',
    'landing_tpl_fitness_desc': 'Workouts, memberships, trainers',
    'landing_tpl_florist': 'Flower studio',
    'landing_tpl_florist_desc': 'Bouquets, delivery, events',
    'landing_tpl_hotel': 'Hotel / B&B',
    'landing_tpl_hotel_desc': 'Rooms, amenities, booking',
    'landing_tpl_it': 'IT services',
    'landing_tpl_it_desc': 'Development, support, cloud',
    'landing_tpl_lawyer': 'Law firm',
    'landing_tpl_lawyer_desc': 'Consulting, practice, trust',
    'landing_tpl_medical': 'Medical clinic',
    'landing_tpl_medical_desc': 'Doctors, services, booking',
    'landing_tpl_music': 'Music studio',
    'landing_tpl_music_desc': 'Recording, production, lessons',
    'landing_tpl_nonprofit': 'Nonprofit',
    'landing_tpl_nonprofit_desc': 'Mission, projects, donations',
    'landing_tpl_pharmacy': 'Pharmacy',
    'landing_tpl_pharmacy_desc': 'Medicines, consultation, delivery',
    'landing_tpl_photographer': 'Photographer',
    'landing_tpl_photographer_desc': 'Portfolio, packages, booking',
    'landing_tpl_real_estate': 'Real estate',
    'landing_tpl_real_estate_desc': 'Listings, agents, consultation',
    'landing_tpl_taxi': 'Taxi / transfer',
    'landing_tpl_taxi_desc': 'Rides, fares, app',
    'landing_tpl_vet': 'Vet clinic',
    'landing_tpl_vet_desc': 'Animal care, vaccination',
    'landing_tpl_wedding': 'Wedding agency',
    'landing_tpl_wedding_desc': 'Event planning, packages',
    'landing_tpl_yoga': 'Yoga / wellness',
    'landing_tpl_yoga_desc': 'Classes, schedule, memberships',
    'nav_tools': 'Tools',
    'phpinfo_hint': 'Full PHP configuration.',
    'phpinfo_open': 'Open phpinfo()',
    'plan_renewed': 'Plan renewed for 1 month',
    'register_domain_selected': 'Your domain',
    'site_dev_mode': 'Developer mode',
    'site_dev_off': 'Disable',
    'site_dev_on': 'Enable',
    'site_errors_empty': 'No errors',
    'site_migrate_queued': 'Migration added to queue',
    'site_migrate_start': 'Add to queue',
    'wp_staging_create': 'Create staging',
    'wp_staging_created': 'Staging created',
}

# Norwegian translations for keys missing from panel-no
PANEL_NO: dict[str, str] = {
    'adv_history_col_action': 'Handling',
    'adv_history_col_detail': 'Detaljer',
    'adv_history_col_when': 'Når',
    'adv_history_count': 'Viser {count} oppføringer',
    'adv_history_empty': 'Ingen oppføringer ennå',
    'adv_perms_apply': 'Bruk tillatelser',
    'adv_perms_current': 'Gjeldende tillatelser',
    'adv_perms_failed': 'Kunne ikke endre tillatelser',
    'adv_perms_folder_hint': 'Din isolerte mappe: {path}',
    'adv_perms_invalid': 'Ugyldig chmod-modus (f.eks. 755 eller 0755)',
    'adv_perms_mode': 'Tilgangstillatelser (chmod)',
    'adv_perms_note': 'Anbefalt 755 for mapper. Bruk filbehandler for enkeltfiler.',
    'adv_perms_presets': 'Hurtigvalg',
    'adv_perms_saved': 'Tilgangstillatelser oppdatert',
    'api_section_hint': 'API-nøkkelinnstillinger for AI-assistenter i support.',
    'btn_add': 'Legg til',
    'checkout_card': 'Kortnummer',
    'checkout_demo': 'Demobetaling — klikk for å aktivere uten faktisk belastning.',
    'checkout_domain': 'Domene',
    'checkout_pay': 'Betal og aktiver',
    'checkout_plan': 'Plan',
    'checkout_title': 'Planbetaling',
    'clients_manage_as': 'Administrer som {name}',
    'cron_added': 'Legg til cron-jobb',
    'db_remote_access': 'Ekstern MySQL',
    'db_remote_off': 'Deaktiver ekstern tilgang',
    'db_remote_on': 'Aktiver ekstern tilgang',
    'dom_add_extra': 'Legg til domene (f.eks. bilohash.com)',
    'domain_available': 'Tilgjengelig',
    'domain_checking': 'Sjekker register…',
    'domain_invalid': 'Angi et gyldig domene (f.eks. mysite.lt)',
    'domain_lookup_error': 'Kunne ikke sjekke registeret. Prøv igjen.',
    'domain_register_cta': 'Hosting + domene',
    'domain_search_btn': 'Sjekk',
    'domain_search_title': 'Finn et domene',
    'domain_taken': 'Opptatt',
    'file_upload': 'Last opp fil',
    'file_uploaded': 'Fil lastet opp',
    'ftp_guide_1': 'Kopier IP, brukernavn og passord nedenfor til FileZilla eller WinSCP.',
    'ftp_guide_2': 'Sett samme passord i hPanel → FTP-kontoer.',
    'ftp_guide_3': 'Opplastingssti: public_html/ditt_brukernavn (f.eks. public_html/demo).',
    'ftp_guide_title': 'Slik kobler du til',
    'ftp_overview_hint': 'FTP-tilkoblingsdata. Hovedpassordet finner du under «Konto».',
    'ftp_pass_reset': 'Generer passord',
    'git_cmd_clone': 'SSH: kloning',
    'git_cmd_pull': 'SSH: oppdatering',
    'git_deploy': 'Deploy fra Git',
    'git_deploy_path': 'Deploy-mappe',
    'git_deployed': 'Deploy i kø',
    'git_err_deploy': 'Deploy-feil',
    'git_err_download': 'Kunne ikke laste ned fra GitHub — sjekk URL, branch eller token.',
    'git_guide_1': 'Lim inn GitHub-repo-URL (https://github.com/user/repo).',
    'git_guide_2': 'For privat repo — Personal Access Token med repo-tilgang.',
    'git_guide_3': 'Klikk «Deploy» — filer lastes til public_html/ditt_brukernavn.',
    'git_guide_4': 'Eller kopier SSH clone/pull-kommando for manuell oppdatering.',
    'git_guide_title': 'Deploy fra GitHub',
    'git_last_deploy': 'Siste deploy',
    'git_no_repo': 'Angi GitHub-URL',
    'git_repo_hint': 'GitHub støttes (offentlige og private med token).',
    'git_status': 'Repository',
    'git_subdir': 'Nettsted-undermappe',
    'git_token': 'GitHub-token',
    'git_token_hint': 'Settings → Developer settings → Personal access tokens (repo).',
    'git_webhook_hint': 'URL for auto-deploy etter push (kommer snart).',
    'git_webhook_title': 'Webhook (fremtidig)',
    'impersonate_banner': '{admin} redigerer som {client}',
    'impersonate_exit': 'Avslutt klientkonto',
    'landing_add_badge': 'Legg til merke',
    'landing_add_column': 'Legg til kolonne',
    'landing_add_download': 'Legg til fil',
    'landing_add_event': 'Legg til hendelse',
    'landing_add_hours_row': 'Legg til rad',
    'landing_add_service': 'Legg til tjeneste',
    'landing_add_step': 'Legg til steg',
    'landing_add_timeline': 'Legg til fase',
    'landing_block_about_desc': 'Fortell hvem dere er.',
    'landing_block_alert': 'Melding',
    'landing_block_alert_desc': 'Viktig melding.',
    'landing_block_app_cta': 'Last ned app',
    'landing_block_app_cta_desc': 'App Store- og Google Play-knapper.',
    'landing_block_badges': 'Tagger / merker',
    'landing_block_badges_desc': 'Ferdigheter eller kategorier.',
    'landing_block_banner': 'Promo-linje',
    'landing_block_banner_desc': 'Smal promo-linje.',
    'landing_block_buttons': 'Knapper',
    'landing_block_buttons_desc': 'Rad med knapper i ulike stiler (primary, outline, ghost).',
    'landing_block_callout': 'Uthevet blokk',
    'landing_block_callout_desc': 'Melding med aksent og knapp.',
    'landing_block_cards': 'Kort',
    'landing_block_cards_desc': 'Fleksible kort med ikon og valgfri knapp.',
    'landing_block_color': 'Blokkfarge',
    'landing_block_color_reset': 'Nettstedstema',
    'landing_block_columns': 'Kolonner',
    'landing_block_columns_desc': 'Tekst i kolonner.',
    'landing_block_comparison': 'Sammenligning',
    'landing_block_comparison_desc': 'Tabell som sammenligner to alternativer.',
    'landing_block_contact_bar': 'Hurtigkontakt',
    'landing_block_contact_bar_desc': 'Telefon, e-post, WhatsApp i en linje.',
    'landing_block_contact_desc': 'Telefon, e-post, adresse.',
    'landing_block_countdown': 'Nedtelling',
    'landing_block_countdown_desc': 'Tidtaker til dato.',
    'landing_block_cta_desc': 'Banner med oppfordring til handling.',
    'landing_block_divider': 'Skillelinje',
    'landing_block_divider_desc': 'Linje mellom seksjoner.',
    'landing_block_download': 'Nedlasting',
    'landing_block_download_desc': 'Filer for nedlasting.',
    'landing_block_events': 'Hendelser',
    'landing_block_events_desc': 'Kommende hendelser.',
    'landing_block_faq_desc': 'Ofte stilte spørsmål og svar.',
    'landing_block_features_desc': 'Fordeler med ikoner.',
    'landing_block_gallery_desc': 'Bilder av arbeid eller produkter.',
    'landing_block_heading': 'Overskrift',
    'landing_block_heading_desc': 'Seksjonsoverskrift.',
    'landing_block_hero_desc': 'Toppbanner med overskrift og knapp.',
    'landing_block_hours': 'Åpningstider',
    'landing_block_hours_desc': 'Åpningstider.',
    'landing_block_icon_list': 'Ikonliste',
    'landing_block_icon_list_desc': 'Fordeler eller steg med ikoner.',
    'landing_block_image': 'Bilde',
    'landing_block_image_desc': 'Ett bilde med bildetekst.',
    'landing_block_info_desc': 'Tall, sitater, fakta.',
    'landing_block_logos_desc': 'Partnerlogoer.',
    'landing_block_map': 'Kart',
    'landing_block_map_desc': 'Adresse eller kart.',
    'landing_block_media_text': 'Bilde + tekst',
    'landing_block_media_text_desc': 'Bilde ved siden av tekst og CTA.',
    'landing_block_menu': 'Meny / prisliste',
    'landing_block_menu_desc': 'Retter eller tjenester med pris.',
    'landing_block_newsletter': 'Nyhetsbrev',
    'landing_block_newsletter_desc': 'E-postabonnement.',
    'landing_block_pricing_desc': 'Planer og priser.',
    'landing_block_quote': 'Sitat',
    'landing_block_quote_desc': 'Stort sitat.',
    'landing_block_services': 'Tjenester',
    'landing_block_services_desc': 'Tjenestekort.',
    'landing_block_social': 'Sosiale medier',
    'landing_block_social_desc': 'Lenker til Facebook, Instagram, LinkedIn osv.',
    'landing_block_spacer': 'Mellomrom',
    'landing_block_spacer_desc': 'Tomt vertikalt mellomrom.',
    'landing_block_stats_bar': 'Statistikklinje',
    'landing_block_stats_bar_desc': 'Horisontale tall (kunder, år, vurdering).',
    'landing_block_steps': 'Steg',
    'landing_block_steps_desc': 'Nummererte steg.',
    'landing_block_team_desc': 'Team eller ansatte.',
    'landing_block_testimonials_desc': 'Kundeanmeldelser.',
    'landing_block_text_desc': 'Tekstavsnitt.',
    'landing_block_timeline': 'Tidslinje',
    'landing_block_timeline_desc': 'Faser eller historie.',
    'landing_block_trust': 'Tillitsmerker',
    'landing_block_trust_desc': 'Sikkerhet, sertifikater, garantier.',
    'landing_block_video': 'Video',
    'landing_block_video_desc': 'YouTube eller Vimeo.',
    'landing_btn_color': 'Knappefarge',
    'landing_btn_text_color': 'Knapptekstfarge',
    'landing_color_inherit': 'Standardtema',
    'landing_countdown_date': 'Nedtellingsdato',
    'landing_embed_url': 'Kart-innbyggings-URL',
    'landing_event_date': 'Dato',
    'landing_event_location': 'Sted',
    'landing_field_group_colors': 'Tekst og knapper',
    'landing_field_group_colors_hint': 'Valgfrie farger for tekst, overskrifter og knapper i denne blokken.',
    'landing_field_group_content': 'Innhold',
    'landing_field_group_items': 'Elementer',
    'landing_field_group_layout': 'Oppsett',
    'landing_file_size': 'Filstørrelse',
    'landing_file_url': 'Fil-URL',
    'landing_heading_color': 'Overskriftsfarge',
    'landing_hint_address': 'Adresse eller serviceområde.',
    'landing_hint_author': 'Hvem som skrev sitatet.',
    'landing_hint_block_color': 'Farge kun for denne blokken. Tomt felt — globalt tema.',
    'landing_hint_btn_color': 'Bakgrunn for CTA-knapper.',
    'landing_hint_btn_text_color': 'Tekst på knapper.',
    'landing_hint_business_name': 'I header, footer og nettleserfanetittel.',
    'landing_hint_caption': 'Bildetekst under bildet (valgfritt).',
    'landing_hint_color_custom': 'Overstyrer valgt temafarge.',
    'landing_hint_countdown_date': 'Nedtellingsdato (ÅÅÅÅ-MM-DD).',
    'landing_hint_cta_text': 'Tekst på knappen.',
    'landing_hint_cta_url': 'Hvor knappen peker — #contact for anker på siden.',
    'landing_hint_email': 'Offentlig e-post for kontakt.',
    'landing_hint_embed_url': 'Kart-innbyggings-URL (Google Maps → Del).',
    'landing_hint_file_url': 'Lenke til PDF eller fil.',
    'landing_hint_footer_style': 'Oppsett for bunntekst.',
    'landing_hint_footer_text': 'Liten tekst eller copyright.',
    'landing_hint_gallery_palette': 'Plassholderfarger hvis bilder ikke er lastet opp ennå.',
    'landing_hint_heading_color': 'h1, h2-overskrifter i blokken.',
    'landing_hint_icon_set': 'Ikonsett for «Fordeler»-blokken.',
    'landing_hint_icon_style': 'Fylte eller konturikoner.',
    'landing_hint_msg_position': 'Hjørne på skjermen for flytende knapper.',
    'landing_hint_msg_style': 'Stabel, utvidelse eller bunnlinje.',
    'landing_hint_nav_label': 'Tekst i menyen.',
    'landing_hint_nav_section': 'Lenker øverst på siden. #about — anker til seksjon.',
    'landing_hint_nav_url': 'Lenke (#about eller full URL).',
    'landing_hint_phone': 'Telefon i kontaktblokken.',
    'landing_hint_quote': 'Kundesitat eller anmeldelse.',
    'landing_hint_section_title': 'Overskrift over elementgruppen.',
    'landing_hint_social': 'Full profillenke (https://…).',
    'landing_hint_subtitle': 'Kort linje under overskriften.',
    'landing_hint_tagline': 'Én linje om virksomheten.',
    'landing_hint_text': 'Hovedtekst — skriv enkelt og tydelig.',
    'landing_hint_text_color': 'Avsnitt og beskrivelser i blokken.',
    'landing_hint_theme_pick': 'Aksentfarge for knapper og lenker.',
    'landing_hint_title': 'Hovedoverskrift som besøkende ser.',
    'landing_hint_variant': 'Velg visningsvariant for seksjonen på siden.',
    'landing_hint_video_url': 'YouTube- eller Vimeo-lenke.',
    'landing_hours_day': 'Dag',
    'landing_hours_time': 'Timer',
    'landing_msg_line_ph': '@lineid',
    'landing_msg_messenger_ph': 'pagename',
    'landing_msg_signal_ph': '+4712345678',
    'landing_msg_skype_ph': 'username',
    'landing_msg_telegram_ph': '@username',
    'landing_msg_viber_ph': '+4712345678',
    'landing_msg_whatsapp_ph': '+4712345678',
    'landing_nav_header': 'Header / meny',
    'landing_panel_left_title': 'Bibliotek',
    'landing_sec_brand_hint': 'Bedriftsnavn og slagord for header og sidetittel.',
    'landing_sec_footer_hint': 'Bunntekst og lenker til sosiale medier.',
    'landing_sec_icons_hint': 'Ikonstil i «Fordeler»-blokken.',
    'landing_sec_nav_hint': 'Menypunkter øverst på siden.',
    'landing_sec_theme_hint': 'Farge for knapper, lenker og aksenter på landingssiden.',
    'landing_service_price': 'Pris (valgfritt)',
    'landing_settings_accordion_hint': 'Én seksjon åpen — klikk overskrift for å bytte.',
    'landing_spoiler_collapse': 'Skjul',
    'landing_spoiler_expand': 'Utvid',
    'landing_text_color': 'Tekstfarge',
    'landing_timeline_year': 'År',
    'landing_tpl_accounting': 'Regnskap',
    'landing_tpl_accounting_desc': 'Bokføring, rapportering, rådgivning',
    'landing_tpl_auto': 'Bilverksted',
    'landing_tpl_auto_desc': 'Reparasjon, diagnostikk, dekk',
    'landing_tpl_bakery': 'Bakeri',
    'landing_tpl_bakery_desc': 'Bakverk, kaker, meny',
    'landing_tpl_barber': 'Barbershop',
    'landing_tpl_barber_desc': 'Klipp, skjegg, prisliste',
    'landing_tpl_beauty': 'Skjønnhetssalong',
    'landing_tpl_beauty_desc': 'Tjenester, priser, booking',
    'landing_tpl_cleaning': 'Rengjøring',
    'landing_tpl_cleaning_desc': 'Rengjøring av hjem og kontor',
    'landing_tpl_coaching': 'Coaching',
    'landing_tpl_coaching_desc': 'Personlig utvikling, mentoring',
    'landing_tpl_construction': 'Bygg',
    'landing_tpl_construction_desc': 'Renovering, prosjekter, estimater',
    'landing_tpl_dentist': 'Tannlege',
    'landing_tpl_dentist_desc': 'Klinikk, tjenester, priser, booking',
    'landing_tpl_education': 'Utdanning / kurs',
    'landing_tpl_education_desc': 'Programmer, instruktører, påmelding',
    'landing_tpl_fitness': 'Treningssenter',
    'landing_tpl_fitness_desc': 'Trening, medlemskap, trenere',
    'landing_tpl_florist': 'Blomsterstudio',
    'landing_tpl_florist_desc': 'Buketter, levering, arrangementer',
    'landing_tpl_hotel': 'Hotell / B&B',
    'landing_tpl_hotel_desc': 'Rom, fasiliteter, booking',
    'landing_tpl_it': 'IT-tjenester',
    'landing_tpl_it_desc': 'Utvikling, support, sky',
    'landing_tpl_lawyer': 'Advokatfirma',
    'landing_tpl_lawyer_desc': 'Rådgivning, praksis, tillit',
    'landing_tpl_medical': 'Medisinsk klinikk',
    'landing_tpl_medical_desc': 'Leger, tjenester, booking',
    'landing_tpl_music': 'Musikkstudio',
    'landing_tpl_music_desc': 'Innspilling, produksjon, undervisning',
    'landing_tpl_nonprofit': 'Ideell organisasjon',
    'landing_tpl_nonprofit_desc': 'Misjon, prosjekter, donasjoner',
    'landing_tpl_pharmacy': 'Apotek',
    'landing_tpl_pharmacy_desc': 'Medisiner, rådgivning, levering',
    'landing_tpl_photographer': 'Fotograf',
    'landing_tpl_photographer_desc': 'Portefølje, pakker, booking',
    'landing_tpl_real_estate': 'Eiendom',
    'landing_tpl_real_estate_desc': 'Objekter, agenter, rådgivning',
    'landing_tpl_taxi': 'Taxi / transfer',
    'landing_tpl_taxi_desc': 'Turer, priser, app',
    'landing_tpl_vet': 'Veterinær',
    'landing_tpl_vet_desc': 'Dyrestell, vaksinasjon',
    'landing_tpl_wedding': 'Bryllupsbyrå',
    'landing_tpl_wedding_desc': 'Arrangementsplanlegging, pakker',
    'landing_tpl_yoga': 'Yoga / velvære',
    'landing_tpl_yoga_desc': 'Timer, timeplan, medlemskap',
    'landing_video_url': 'Videolenke',
    'log_action_ai_settings': 'AI API-innstillinger',
    'log_action_backup_create': 'Sikkerhetskopi',
    'log_action_cache_clear': 'Tøm cache',
    'log_action_cache_toggle': 'Cache',
    'log_action_db_create': 'Database opprettet',
    'log_action_domain_primary': 'Primærdomene',
    'log_action_extra_domain_add': 'Domene lagt til',
    'log_action_fm': 'Filbehandler',
    'log_action_folder_chmod': 'Endre mappetillatelser',
    'log_action_landing_publish': 'Landingsside publisert',
    'log_action_master_password_changed': 'Passord endret',
    'log_action_panel_visit': 'Sidevisning',
    'log_action_php_settings': 'PHP-innstillinger',
    'log_action_ssh_toggle': 'SSH-tilgang',
    'log_action_subdomain_add': 'Underdomene lagt til',
    'log_action_wp_install': 'WordPress installert',
    'nav_clients': 'Kunder',
    'nav_group_api': 'API',
    'nav_tools': 'Verktøy',
    'phpinfo_hint': 'Full PHP-konfigurasjon.',
    'phpinfo_open': 'Åpne phpinfo()',
    'plan_renewed': 'Plan fornyet i 1 måned',
    'register_domain_selected': 'Ditt domene',
    'site_dev_mode': 'Utviklermodus',
    'site_dev_off': 'Deaktiver',
    'site_dev_on': 'Aktiver',
    'site_errors_empty': 'Ingen feil',
    'site_migrate_queued': 'Migrering lagt i kø',
    'site_migrate_start': 'Legg i kø',
    'ssh_disabled_msg': 'SSH deaktivert',
    'ssh_enabled_msg': 'SSH aktivert',
    'ssh_password_set': 'Konfigurert på serveren',
    'tab_api_ai': 'AI (Grok / ChatGPT)',
    'tip_clients': 'Åpne panelet på vegne av klienten — alle endringer lagres i kontoen deres.',
    'wp_staging_create': 'Opprett staging',
    'wp_staging_created': 'Staging opprettet',
}

# Public uk additions (keys in en but not uk) — Ukrainian translations
PUBLIC_UK_EXTRA: dict[str, str] = {
    'feat_domains': 'Домени та DNS',
    'feat_domains_desc': 'Пошук, реєстрація та керування DNS в одній панелі.',
    'feat_hosting': 'Веб-хостинг',
    'feat_hosting_desc': 'SSD, SSL, FTP, SSH і панель як у hPanel — але під брендом BILOHASH.',
    'register_password_demo': 'Пароль (демо: demo)',
    'register_password_demo_hint': 'Демо-режим: пароль попередньо заповнено як «demo».',
    'register_plan': 'Оберіть тариф',
    'register_submit': 'Створити акаунт і відкрити панель',
    'register_domain': 'Домен (необовʼязково)',
    'register_domain_hint': 'Перевірте доступність і додайте домен до акаунту.',
    'register_domain_check': 'Перевірити домен',
    'register_domain_available': 'Домен доступний',
    'register_domain_taken': 'Домен зайнятий',
}

# Public no additions for keys that may be missing
PUBLIC_NO_EXTRA: dict[str, str] = {
    'feat_domains': 'Domener og DNS',
    'feat_domains_desc': 'Søk, registrering og DNS-administrasjon i ett panel.',
    'feat_hosting': 'Webhosting',
    'feat_hosting_desc': 'SSD, SSL, FTP, SSH og panel som hPanel — under BILOHASH-merkevaren.',
    'register_password_demo': 'Passord (demo: demo)',
    'register_password_demo_hint': 'Demomodus: passord forhåndsutfylt som «demo».',
    'register_plan': 'Velg plan',
    'register_submit': 'Opprett konto og åpne panel',
    'register_domain': 'Domene (valgfritt)',
    'register_domain_hint': 'Sjekk tilgjengelighet og legg til domene på kontoen.',
    'register_domain_check': 'Sjekk domene',
    'register_domain_available': 'Domenet er tilgjengelig',
    'register_domain_taken': 'Domenet er opptatt',
}


def strip_quotes(val: str) -> str:
    if (val.startswith("'") and val.endswith("'")) or (val.startswith('"') and val.endswith('"')):
        inner = val[1:-1]
        return inner.replace("\\'", "'").replace('\\\\', '\\')
    return val


def merge_panel(target: dict[str, str], uk: dict[str, str], translations: dict[str, str], lang: str) -> list[str]:
    missing = []
    ref_order = list(uk.keys())
    for key in uk:
        if key not in target:
            if key in translations:
                target[key] = php_escape(translations[key])
                missing.append(key)
            else:
                print(f'ERROR: No {lang} translation for panel key: {key}', file=sys.stderr)
                print(f'  UK: {strip_quotes(uk[key])}', file=sys.stderr)
                missing.append(key)
    return missing


def main() -> int:
    panel_uk = parse_php_lang(LANG / 'panel-uk.php')
    panel_en = parse_php_lang(LANG / 'panel-en.php')
    panel_no = parse_php_lang(LANG / 'panel-no.php')
    public_uk = parse_php_lang(LANG / 'uk.php')
    public_en = parse_php_lang(LANG / 'en.php')
    public_no = parse_php_lang(LANG / 'no.php')

    before = {
        'panel-en': len(panel_en),
        'panel-no': len(panel_no),
        'public-uk': len(public_uk),
        'public-en': len(public_en),
        'public-no': len(public_no),
    }

    # Public: add en extras to uk
    for key, text in PUBLIC_UK_EXTRA.items():
        if key not in public_uk:
            public_uk[key] = php_escape(text)

    # Public: sync no from uk/en
    for key in public_uk:
        if key not in public_no:
            if key in PUBLIC_NO_EXTRA:
                public_no[key] = php_escape(PUBLIC_NO_EXTRA[key])
            elif key in public_en:
                # fallback: use en value stripped (should not happen for our extras)
                public_no[key] = public_en[key]
            else:
                print(f'ERROR: No public no translation for: {key}', file=sys.stderr)

    # Public: ensure en has all uk keys
    for key in public_uk:
        if key not in public_en:
            print(f'ERROR: public en missing key from uk: {key}', file=sys.stderr)

    en_untranslated = merge_panel(panel_en, panel_uk, PANEL_EN, 'en')
    no_untranslated = merge_panel(panel_no, panel_uk, PANEL_NO, 'no')

    uk_order = list(parse_php_lang(LANG / 'panel-uk.php').keys())
    write_php_lang(LANG / 'panel-en.php', panel_en, uk_order)
    write_php_lang(LANG / 'panel-no.php', panel_no, uk_order)

    uk_pub_order = list(parse_php_lang(LANG / 'uk.php').keys())
    # Re-read uk after merge for order - use en order as base for public
    public_uk_order = list(public_uk.keys())
    write_php_lang(LANG / 'uk.php', public_uk, public_uk_order)
    write_php_lang(LANG / 'no.php', public_no, public_uk_order)

    after = {
        'panel-en': len(panel_en),
        'panel-no': len(panel_no),
        'public-uk': len(public_uk),
        'public-en': len(public_en),
        'public-no': len(public_no),
    }

    print('BEFORE:', before)
    print('AFTER:', after)
    print('panel-en added:', len(en_untranslated), en_untranslated)
    print('panel-no added:', len(no_untranslated), no_untranslated)
    extra_en = [k for k in public_en if k not in public_uk]
    print('public en extras (should be 0 after uk sync):', extra_en)

    failed = [k for k in panel_uk if k not in panel_en] + [k for k in panel_uk if k not in panel_no]
    failed += [k for k in public_uk if k not in public_en or k not in public_no]
    if failed:
        print('FAILED KEYS:', failed)
        return 1
    print('All keys synced successfully.')
    return 0


if __name__ == '__main__':
    sys.exit(main())