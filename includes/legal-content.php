<?php
declare(strict_types=1);

/**
 * Legal documents for Solaskinner Hosting CMS.
 * Aligned with: GDPR (EU 2016/679), Norwegian Personopplysningsloven,
 * Angrerettloven / Consumer Rights Directive 2011/83/EU, Ekomloven / ePrivacy,
 * Markedsføringsloven, Forbrukerkjøpsloven principles, DSA hosting safe harbour,
 * Bokføringsloven retention. Not a substitute for licensed legal advice.
 */

function hs_legal_ctx(string $lang): array
{
    $brand = hs_legal_brand();
    $domain = defined('HS_PRIMARY_DOMAIN') ? HS_PRIMARY_DOMAIN : 'solaskinner.com';
    $email = 'support@solaskinner.com';
    $privacy = hs_legal_url('privacy.php', $lang);
    $cookies = hs_legal_url('cookies.php', $lang);
    $terms = hs_legal_url('terms.php', $lang);
    $domains = hs_legal_url('domain-registration.php', $lang);
    return compact('brand', 'domain', 'email', 'privacy', 'cookies', 'terms', 'domains');
}

/** @return array<string, mixed> */
function hs_legal_terms_doc(string $lang): array
{
    $c = hs_legal_ctx($lang);
    $all = [
        'no' => [
            'title' => 'Vilkår for hostingtjenester',
            'last_updated' => 'Sist oppdatert: 16. juli 2026',
            'intro' => 'Disse vilkårene regulerer kjøp og bruk av webhotell, domener, e-post, SSL, CMS-økosystem og tilhørende tjenester under merkenavnet <strong>' . $c['brand'] . '</strong> (' . $c['domain'] . '). Tjenestene tilbys fra <strong>Norge</strong> til kunder i Norge, EØS/EU og internasjonalt. Ved å opprette konto, godta vilkårene i kassen eller betale for tjenesten, inngår du avtale på disse vilkårene. Les også <a href="' . $c['privacy'] . '">personvernerklæringen</a> og <a href="' . $c['cookies'] . '">cookie-erklæringen</a>.',
            'sections' => [
                [
                    'title' => '1. Avtaleparter og identifikasjon av selger',
                    'body' => '<p><strong>Tilbyder / selger:</strong><br>Ruslan Bilohash (privatperson / enkeltpersonforetak-lignende virksomhet)<br>Drammen, Norge<br>E-post: <a href="mailto:' . $c['email'] . '">' . $c['email'] . '</a><br>Telefon: <a href="tel:+4746255885">+47 462 55 885</a><br>Nettside: https://' . $c['domain'] . '/</p>'
                        . '<p><strong>Kunde:</strong> den fysiske eller juridiske personen som registrerer konto, bestiller hosting, domene eller andre tjenester.</p>'
                        . '<p>Opplysninger som kreves ved fjernsalg til forbrukere (angrerettloven / forbrukerrettighetsdirektivet 2011/83/EU) gis før betaling: pris inkl. eventuelle avgifter, abonnementsperiode, hva som inngår, og hvordan du sier opp.</p>',
                ],
                [
                    'title' => '2. Tjenester',
                    'body' => '<p>Vi leverer blant annet:</p><ul>'
                        . '<li>delt webhotell (SSD) og lagringskvoter iht. valgt plan;</li>'
                        . '<li>kundepanel, filbehandling, databaser, e-post der inkludert;</li>'
                        . '<li>CMS-/app-installasjon og tilhørende verktøy i økosystemet;</li>'
                        . '<li>domeneregistrering og DNS via autoriserte registrarer;</li>'
                        . '<li>SSL, sikkerhetskopier der plan inkluderer det;</li>'
                        . '<li>teknisk support via e-post/panel.</li></ul>'
                        . '<p>Omfang, grenser (disk, CPU, båndbredde, antall domener/postkasser) følger den plan som er bestilt. Infrastruktur leveres via etablerte datasenter- og registrarleverandører (bl.a. i Europa og/eller USA) under databehandleravtaler der det kreves.</p>'
                        . '<p>Enkelte planer kan omfatte egen cPanel-konto via vår forhandler-WHM (f.eks. Namecheap Nebula). Slike underkontoer er underlagt både disse vilkårene og registrarens/hostingleverandørens regler.</p>',
                ],
                [
                    'title' => '3. Konto, alder og legitimering',
                    'body' => '<p>Du må oppgi korrekte opplysninger (navn, e-post, adresse, land, og for bedrifter: org.nr./MVA der relevant). Du er ansvarlig for brukernavn, passord og all aktivitet på kontoen. Deling av innlogging med uvedkommende utenfor ditt team er ikke tillatt.</p>'
                        . '<p><strong>Minstealder:</strong> 18 år. Personer mellom 16 og 18 kan registrere seg med foresattes samtykke der norsk/EU-lov tillater det. Vi kan be om dokumentasjon ved mistanke om feil opplysninger eller misbruk.</p>',
                ],
                [
                    'title' => '4. Priser, betaling, MVA og automatisk fornyelse',
                    'body' => '<p>Priser vises før bestilling i NOK eller annen angitt valuta. Eventuell merverdiavgift (MVA) og andre avgifter angis der de gjelder etter norsk skatteregelverk og EU-regler for digitale tjenester (MOSS/OSS der relevant).</p>'
                        . '<p>Betaling skjer via de metoder som tilbys (kort, Stripe, manuell faktura m.m.). Abonnement fornyes automatisk for samme periode med mindre du sier opp før fornyelsesdato. Ved manglende betaling kan tjenesten suspenderes etter varsel, og slettes etter ytterligere karens.</p>'
                        . '<p>Prisendringer varsles minst <strong>30 dager</strong> før de får virkning for eksisterende abonnement. Vesentlig prisøkning gir deg rett til å si opp uten ekstra kostnad før ikrafttredelse.</p>'
                        . '<p>Eventuell prøveperiode (f.eks. 30 dager) er tydelig merket ved registrering. Etter utløp gjelder ordinær pris med mindre oppsigelse er mottatt i tide.</p>',
                ],
                [
                    'title' => '5. Angrerett (forbrukere i Norge og EØS/EU)',
                    'body' => '<p>Er du <strong>forbruker</strong> (privatperson) og har inngått avtale på nettet, har du som hovedregel <strong>14 dagers angrerett</strong> etter angrerettloven og forbrukerrettighetsdirektivet 2011/83/EU, regnet fra avtaleinngåelsen.</p>'
                        . '<p><strong>Unntak for digitale tjenester:</strong> angreretten bortfaller dersom levering av digitalt innhold/tjeneste er påbegynt med ditt uttrykkelige forhåndssamtykke, og du har erkjent at angreretten tapes ved fullføring (typisk: du aktiverer hosting umiddelbart). Der unntaket gjelder, informeres du før betaling.</p>'
                        . '<p><strong>Slik bruker du angreretten:</strong> send en klar melding til <a href="mailto:' . $c['email'] . '">' . $c['email'] . '</a> innen fristen (angi navn, e-post, bestillingsdato). Vi tilbakebetaler beløpet uten unødig opphold, senest 14 dager etter at vi mottok melding, med samme betalingsmiddel der det er praktisk.</p>'
                        . '<p>Næringsdrivende (B2B) har ikke lovbestemt angrerett med mindre annet er avtalt skriftlig.</p>',
                ],
                [
                    'title' => '6. Domener',
                    'body' => '<p>Domeneregistrering skjer via ICANN-akkreditert registrar <strong>Namecheap</strong> (Newfold Digital / Namecheap, Inc.). <strong>' . $c['brand'] . '</strong> er forhandler/reseller — ikke selvstendig ICANN-registrar. Du er registrert innehaver (registrant) og må overholde ICANN, Namecheap og TLD-regler (inkl. .no via Norid der relevant).</p>'
                        . '<p>Ved bestilling av domene godtar du også Namecheaps registreringsavtale og relaterte juridiske vilkår (pass-through). Detaljer, lovpålagte plikter, WHOIS/RDAP, fornyelse, angrerett og personvern for domener: <a href="' . $c['domains'] . '">Domeneregistreringspolicy</a>.</p>'
                        . '<p>Fornyelse, korrekte kontaktopplysninger og eierskap er ditt ansvar. Domener som ikke fornyes kan slettes etter registrarens policy. Vi bistår med DNS og bestilling via API.</p>',
                ],
                [
                    'title' => '7. Akseptabel bruk (AUP)',
                    'body' => '<p>Det er forbudt å bruke tjenesten til:</p><ul>'
                        . '<li>ulovlig innhold eller virksomhet under norsk eller EU-lov;</li>'
                        . '<li>phishing, spam, malware, botnett, DDoS, uautorisert hacking;</li>'
                        . '<li>materiale som utnytter barn seksuelt eller annet ulovlig innhold om barn;</li>'
                        . '<li>hatkriminalitet, terrorinnhold, grov trussel eller svindel;</li>'
                        . '<li>systematisk brudd på opphavsrett eller andre immaterielle rettigheter;</li>'
                        . '<li>kryptomining uten skriftlig avtale;</li>'
                        . '<li>ressursmisbruk som skader andre kunder eller infrastrukturen.</li></ul>'
                        . '<p>Ved brudd kan vi begrense, suspendere eller avslutte tjenesten — med varsel der det er rimelig, eller umiddelbart ved alvorlige brudd, rettslig pålegg eller akutt sikkerhetsrisiko.</p>',
                ],
                [
                    'title' => '8. Kundens innhold, opphavsrett og mellommannsansvar',
                    'body' => '<p>Du eier og er ansvarlig for alt innhold du lagrer, publiserer eller sender via tjenesten. Du gir oss en begrenset, ikke-eksklusiv lisens til å hoste, sikkerhetskopiere, overføre og teknisk behandle innholdet for å levere tjenesten.</p>'
                        . '<p>Som hostingleverandør opptrer vi som <strong>mellommann / lagringstjeneste</strong> (ehandelsdirektivet / Digital Services Act for hosting) for kundens nettinnhold: vi overvåker ikke generelt alt innhold, men fjerner eller blokkerer ulovlig innhold når vi får faktisk kunnskap eller gyldig varsel, og handler raskt.</p>'
                        . '<p><strong>Melding om ulovlig innhold:</strong> send e-post til ' . $c['email'] . ' med URL, beskrivelse og dokumentasjon. Vi vurderer og svarer innen rimelig tid.</p>'
                        . '<p>Du er selv behandlingsansvarlig for personopplysninger om dine nettstedsbesøkende. Se personvernerklæringen for vårt ansvar som behandlingsansvarlig vs. databehandler.</p>',
                ],
                [
                    'title' => '9. Tilgjengelighet, vedlikehold og support',
                    'body' => '<p>Vi tilstreber høy oppetid, men garanterer ikke 100 % uten særskilt SLA-avtale. Planlagt vedlikehold varsles når det er praktisk mulig. Support ytes via e-post/panel; responstid avhenger av plan og belastning. Kritisk sikkerhet kan kreve umiddelbar handling uten forutgående varsel.</p>',
                ],
                [
                    'title' => '10. Oppsigelse, sletting av data og refusjon',
                    'body' => '<p>Du kan si opp abonnement fra kundepanel eller ved melding til ' . $c['email'] . '. Oppsigelse trer i kraft ved utløp av betalt periode med mindre annet er avtalt. Etter opphør kan data slettes etter en karensperiode (typisk opptil 30 dager), med mindre lov krever lengre lagring (f.eks. regnskap).</p>'
                        . '<p>Forhåndsbetalte perioder refunderes ikke automatisk etter at tjenesten er levert, utover angrerett der den gjelder, eller der norsk/EU forbrukervern krever det. Domener er ofte ikke-refunderbare etter registrarens regler når de er registrert.</p>',
                ],
                [
                    'title' => '11. Ansvarsbegrensning',
                    'body' => '<p>Tjenesten leveres med profesjonell omsorg («som den er» innenfor det som er rimelig). Vi er ikke ansvarlige for indirekte tap, tapt fortjeneste, tapt data hos kunde som ikke er dekket av avtalt backup, eller tap forårsaket av tredjepart, med mindre det skyldes grov uaktsomhet eller forsett.</p>'
                        . '<p>Vårt maksimale erstatningsansvar overfor <strong>næringskunder</strong> er begrenset til det beløpet kunden har betalt oss for berørte tjenester de siste 12 månedene. <strong>Forbrukeres ufravikelige rettigheter</strong> etter norsk og EU-lovgivning går foran denne begrensningen.</p>',
                ],
                [
                    'title' => '12. Personvern, cookies og markedsføring',
                    'body' => '<p>Behandling av personopplysninger følger <a href="' . $c['privacy'] . '">personvernerklæringen</a> (GDPR, Personopplysningsloven). Cookies: <a href="' . $c['cookies'] . '">cookie-erklæring</a> (Ekomloven / ePrivacy). Markedsføring per e-post krever separat samtykke (Markedsføringsloven § 15), med enkel mulighet til å melde seg av.</p>',
                ],
                [
                    'title' => '13. Immaterielle rettigheter til plattformen',
                    'body' => '<p>Programvare, design, logoer og dokumentasjon som tilhører ' . $c['brand'] . ' forblir vår (eller våre lisensgiveres) eiendom. Du får en begrenset bruksrett i abonnementsperioden. Reverse engineering av plattformen utover det loven tillater er forbudt.</p>',
                ],
                [
                    'title' => '14. Force majeure',
                    'body' => '<p>Vi er ikke ansvarlige for forsinkelse eller manglende oppfyllelse som skyldes forhold utenfor rimelig kontroll (krig, naturkatastrofe, streik, større nettstans hos underleverandør, myndighetspålegg m.m.), forutsatt at vi varsler og forsøker å begrense skaden.</p>',
                ],
                [
                    'title' => '15. Lovvalg, tvister og klageordninger',
                    'body' => '<p>Avtalen er underlagt <strong>norsk rett</strong>.</p>'
                        . '<p><strong>Næringskunder:</strong> verneting Oslo tingrett, med mindre preseptorisk lov sier noe annet.</p>'
                        . '<p><strong>Forbrukere i Norge:</strong> Forbrukerrådet, Forbrukertilsynet og alminnelige domstoler. <strong>Forbrukere i EØS/EU:</strong> du kan også bruke tvisteløsning i bostedslandet etter EU-regler. EU nettbasert tvisteløsning (ODR): <a href="https://ec.europa.eu/consumers/odr" rel="noopener noreferrer">ec.europa.eu/consumers/odr</a>.</p>'
                        . '<p>Personvernklager: <a href="https://www.datatilsynet.no" rel="noopener noreferrer">Datatilsynet</a> (Norge) eller tilsynsmyndighet i ditt EØS-land.</p>',
                ],
                [
                    'title' => '16. Endringer i vilkårene',
                    'body' => '<p>Vesentlige endringer varsles via e-post eller panel minst 30 dager før ikrafttredelse. Fortsatt bruk etter ikrafttredelse regnes som aksept, med mindre du sier opp før. Gjeldende versjon: <a href="' . $c['terms'] . '">' . $c['terms'] . '</a>.</p>',
                ],
            ],
            'contact_title' => 'Spørsmål om vilkårene?',
            'contact_text' => 'Kontakt oss på e-post. Vi svarer normalt innen 5 virkedager.',
        ],
        'en' => [
            'title' => 'Terms of Service — Hosting',
            'last_updated' => 'Last updated: 16 July 2026',
            'intro' => 'These terms govern the purchase and use of web hosting, domains, email, SSL, CMS tools and related services under the <strong>' . $c['brand'] . '</strong> brand (' . $c['domain'] . '). Services are offered from <strong>Norway</strong> to customers in Norway, the EU/EEA and internationally. By creating an account, accepting at checkout or paying for a service, you enter into a contract on these terms. See also the <a href="' . $c['privacy'] . '">Privacy Policy</a> and <a href="' . $c['cookies'] . '">Cookie Policy</a>.',
            'sections' => [
                [
                    'title' => '1. Parties and trader identification',
                    'body' => '<p><strong>Provider:</strong><br>Ruslan Bilohash (private individual / sole trader-style business)<br>Drammen, Norway<br>Email: <a href="mailto:' . $c['email'] . '">' . $c['email'] . '</a><br>Phone: <a href="tel:+4746255885">+47 462 55 885</a><br>Website: https://' . $c['domain'] . '/</p>'
                        . '<p><strong>Customer:</strong> any natural or legal person who registers an account or orders services.</p>'
                        . '<p>Pre-contract information required for distance contracts with consumers (Norwegian Cancellation Act / Consumer Rights Directive 2011/83/EU) is provided before payment: price, period, what is included, and how to cancel.</p>',
                ],
                [
                    'title' => '2. Services',
                    'body' => '<p>We provide shared SSD hosting, client panel, CMS/app tools, domain registration and DNS via accredited registrars, email where included, SSL, backups where included, and technical support. Scope and limits follow your plan. Infrastructure is delivered through established data-centre and registrar providers (Europe and/or the US) under data-processing agreements where required. Some plans may include a separate cPanel account via our reseller WHM; those accounts are also subject to the underlying provider’s rules.</p>',
                ],
                [
                    'title' => '3. Account and age',
                    'body' => '<p>You must provide accurate details. You are responsible for credentials and all account activity. Minimum age: 18 (or 16 with parental consent where the law allows).</p>',
                ],
                [
                    'title' => '4. Pricing, payment, VAT and renewal',
                    'body' => '<p>Prices are shown before checkout in NOK or another stated currency. VAT and taxes apply under Norwegian and EU digital-services rules where relevant. Subscriptions renew automatically unless cancelled before the renewal date. Non-payment may lead to suspension after notice. Price changes for existing customers are notified at least 30 days in advance. Trial periods, if any, are clearly marked at signup.</p>',
                ],
                [
                    'title' => '5. Right of withdrawal (consumers in Norway / EU/EEA)',
                    'body' => '<p>If you are a <strong>consumer</strong>, you generally have a <strong>14-day right of withdrawal</strong> from the contract date under the Norwegian Cancellation Act and Directive 2011/83/EU.</p>'
                        . '<p><strong>Digital services exception:</strong> the right is lost if performance began with your express prior consent and you acknowledged that you lose the right once delivery has started (e.g. hosting activated immediately). You are informed before payment where this applies.</p>'
                        . '<p>To withdraw: email <a href="mailto:' . $c['email'] . '">' . $c['email'] . '</a> within the deadline with your name, email and order date. Refunds within 14 days of receipt of notice, preferably via the same payment method. Business customers (B2B) have no statutory withdrawal right unless agreed in writing.</p>',
                ],
                [
                    'title' => '6. Domains',
                    'body' => '<p>Domain registration is performed through the ICANN-accredited registrar <strong>Namecheap</strong> (Newfold Digital / Namecheap, Inc.). <strong>' . $c['brand'] . '</strong> acts as a reseller — not as an independent ICANN registrar. You are the registrant and must comply with ICANN, Namecheap and TLD rules (including .no / Norid where relevant).</p>'
                        . '<p>By ordering a domain you also accept Namecheap’s Registration Agreement and related legal terms (pass-through). Full details, statutory duties, WHOIS/RDAP, renewal, withdrawal and privacy for domains: <a href="' . $c['domains'] . '">Domain Registration Policy</a>.</p>'
                        . '<p>Renewal, accurate contact data and ownership remain your responsibility. Domains not renewed may be deleted under the registrar’s policy. We assist with DNS and ordering via API.</p>',
                ],
                [
                    'title' => '7. Acceptable use',
                    'body' => '<p>Prohibited: illegal content or activity under Norwegian or EU law; phishing, spam, malware, attacks; child sexual abuse material; hate crime, terrorism content, fraud; systematic copyright infringement; crypto-mining without written agreement; resource abuse harming others. We may suspend or terminate after notice where reasonable, or immediately for serious breaches, court orders or acute security risks.</p>',
                ],
                [
                    'title' => '8. Your content and intermediary role',
                    'body' => '<p>You own and are responsible for content you store or publish. You grant us a limited licence to host, back up and process it to deliver the service. As a hosting provider we act as an intermediary/storage service (e-Commerce Directive / DSA hosting): we do not generally monitor all content, but remove or disable illegal content when we obtain actual knowledge or a valid notice. Report illegal content to ' . $c['email'] . ' with URL and details. You are controller for personal data of your site visitors; see the Privacy Policy for our controller vs processor roles.</p>',
                ],
                [
                    'title' => '9. Availability and support',
                    'body' => '<p>We aim for high uptime but do not guarantee 100% without a separate SLA. Planned maintenance is announced when practical. Support via email/panel; response times depend on plan.</p>',
                ],
                [
                    'title' => '10. Cancellation, deletion and refunds',
                    'body' => '<p>Cancel from the panel or by email. Cancellation takes effect at the end of the paid period. Data may be deleted after a grace period (typically up to 30 days) unless law requires longer retention. Prepaid fees are not automatically refunded after delivery, except where the withdrawal right applies or mandatory consumer law requires a refund. Domains are often non-refundable once registered.</p>',
                ],
                [
                    'title' => '11. Limitation of liability',
                    'body' => '<p>Service is provided with professional care. We are not liable for indirect loss, lost profits or third-party losses unless caused by gross negligence or intent. Maximum liability to business customers: fees paid in the last 12 months for affected services. Mandatory consumer rights under Norwegian and EU law prevail.</p>',
                ],
                [
                    'title' => '12. Privacy, cookies and marketing',
                    'body' => '<p><a href="' . $c['privacy'] . '">Privacy Policy</a> (GDPR, Norwegian Personal Data Act). <a href="' . $c['cookies'] . '">Cookie Policy</a> (Ekomloven / ePrivacy). Marketing email requires separate opt-in (Marketing Control Act § 15) with easy opt-out.</p>',
                ],
                [
                    'title' => '13. Platform IP',
                    'body' => '<p>Software, design and documentation of ' . $c['brand'] . ' remain our (or our licensors’) property. You receive a limited right to use the platform during the subscription.</p>',
                ],
                [
                    'title' => '14. Force majeure',
                    'body' => '<p>We are not liable for delay or failure caused by events beyond reasonable control, provided we notify you and take reasonable steps to mitigate.</p>',
                ],
                [
                    'title' => '15. Governing law and disputes',
                    'body' => '<p><strong>Norwegian law</strong> applies. Business venue: Oslo District Court unless mandatory law provides otherwise. Norwegian consumers: Forbrukerrådet / courts. EU/EEA consumers may also use dispute resolution in their country of residence. EU ODR: <a href="https://ec.europa.eu/consumers/odr" rel="noopener noreferrer">ec.europa.eu/consumers/odr</a>. Privacy complaints: <a href="https://www.datatilsynet.no" rel="noopener noreferrer">Datatilsynet</a> or your local EU/EEA authority.</p>',
                ],
                [
                    'title' => '16. Changes',
                    'body' => '<p>Material changes are notified at least 30 days in advance. Current version: <a href="' . $c['terms'] . '">' . $c['terms'] . '</a>.</p>',
                ],
            ],
            'contact_title' => 'Questions about these terms?',
            'contact_text' => 'Email us — we normally reply within 5 business days.',
        ],
        'uk' => [
            'title' => 'Умови надання хостинг-послуг',
            'last_updated' => 'Останнє оновлення: 16 липня 2026',
            'intro' => 'Ці умови регулюють придбання та використання веб-хостингу, доменів, пошти, SSL, CMS і пов’язаних послуг під брендом <strong>' . $c['brand'] . '</strong> (' . $c['domain'] . '). Послуги надаються з <strong>Норвегії</strong> клієнтам у Норвегії, ЄС/ЄЕЗ та інших країнах. Створюючи акаунт, приймаючи умови під час оплати або оплачуючи послугу, ви укладаєте договір. Див. також <a href="' . $c['privacy'] . '">Політику конфіденційності</a> та <a href="' . $c['cookies'] . '">Політику cookie</a>.',
            'sections' => [
                [
                    'title' => '1. Сторони та ідентифікація продавця',
                    'body' => '<p><strong>Надавач:</strong><br>Ruslan Bilohash (фізична особа / sole trader)<br>Drammen, Норвегія<br>Email: <a href="mailto:' . $c['email'] . '">' . $c['email'] . '</a><br>Тел.: <a href="tel:+4746255885">+47 462 55 885</a><br>Сайт: https://' . $c['domain'] . '/</p>'
                        . '<p><strong>Клієнт:</strong> фізична або юридична особа, що реєструє акаунт чи замовляє послуги.</p>'
                        . '<p>Перед оплатою надається інформація, обов’язкова для дистанційних договорів зі споживачами (норвезький Angrerettloven / директива 2011/83/EU): ціна, період, склад послуги, порядок скасування.</p>',
                ],
                [
                    'title' => '2. Послуги',
                    'body' => '<p>Спільний SSD-хостинг, панель клієнта, CMS/додатки, реєстрація доменів і DNS, пошта (за планом), SSL, бекапи (за планом), техпідтримка. Обсяг і ліміти — згідно з тарифом. Інфраструктура — через дата-центри та реєстраторів у ЄС/США з DPA. Окремі тарифи можуть включати cPanel через reseller WHM (Nebula) — також правила провайдера.</p>',
                ],
                [
                    'title' => '3. Акаунт і вік',
                    'body' => '<p>Надавайте достовірні дані. Ви відповідаєте за логін і всю активність. Мінімальний вік: 18 років (16 — зі згодою батьків, де дозволено).</p>',
                ],
                [
                    'title' => '4. Ціни, оплата, ПДВ і автопродовження',
                    'body' => '<p>Ціни до оформлення (NOK або інша валюта). ПДВ/податки — за норвезькими та EU-правилами для цифрових послуг. Автопродовження, якщо не скасовано. Несплата — призупинення після попередження. Зміна цін для чинних підписок — мінімум за 30 днів.</p>',
                ],
                [
                    'title' => '5. Право відступу (споживачі Норвегії / ЄС/ЄЕЗ)',
                    'body' => '<p>Якщо ви <strong>споживач</strong>, зазвичай маєте <strong>14 днів</strong> на відступ від договору (Angrerettloven, директива 2011/83/EU).</p>'
                        . '<p><strong>Виняток для цифрових послуг:</strong> право втрачається, якщо виконання почалось за вашою явною попередньою згодою і ви визнали втрату права (наприклад, хостинг активовано одразу). Про це повідомляємо перед оплатою.</p>'
                        . '<p>Для відступу: email <a href="mailto:' . $c['email'] . '">' . $c['email'] . '</a> у строк. Повернення коштів — протягом 14 днів після повідомлення. B2B не має статутного права відступу, якщо інше не узгоджено.</p>',
                ],
                [
                    'title' => '6. Домени',
                    'body' => '<p>Реєстрація доменів виконується через ICANN-акредитованого реєстратора <strong>Namecheap</strong> (Newfold Digital / Namecheap, Inc.). <strong>' . $c['brand'] . '</strong> виступає як реселер — не як самостійний ICANN-реєстратор. Ви — реєстрант і зобов’язані дотримуватись правил ICANN, Namecheap і TLD (вкл. .no / Norid).</p>'
                        . '<p>Замовляючи домен, ви також приймаєте Registration Agreement Namecheap і пов’язані юридичні умови (pass-through). Повний опис, вимоги закону, WHOIS/RDAP, подовження, відмова і приватність: <a href="' . $c['domains'] . '">Політика реєстрації доменів</a>.</p>'
                        . '<p>Подовження, актуальні контактні дані та власність — ваша відповідальність. Неподовжені домени можуть бути видалені за політикою реєстратора. Ми допомагаємо з DNS і замовленням через API.</p>',
                ],
                [
                    'title' => '7. Допустиме використання',
                    'body' => '<p>Заборонено: незаконний контент/діяльність; фішинг, спам, malware, атаки; матеріали сексуальної експлуатації дітей; мова ворожнечі, тероризм, шахрайство; системне порушення авторських прав; майнінг без угоди; зловживання ресурсами. Призупинення з попередженням або негайно при серйозних порушеннях.</p>',
                ],
                [
                    'title' => '8. Контент клієнта та роль посередника',
                    'body' => '<p>Контент належить вам. Ми отримуємо обмежену ліцензію на хостинг і бекапи. Як хостинг-провайдер ми — посередник/сховище (e-Commerce Directive / DSA): не моніторимо все, але видаляємо незаконний контент після фактичного знання чи валідного повідомлення. Скарги: ' . $c['email'] . '. Ви — контролер даних відвідувачів своїх сайтів.</p>',
                ],
                [
                    'title' => '9. Доступність і підтримка',
                    'body' => '<p>Прагнемо високої доступності без гарантії 100% без окремого SLA. Планові роботи анонсуємо за можливості. Підтримка — email/панель.</p>',
                ],
                [
                    'title' => '10. Скасування, видалення даних і повернення',
                    'body' => '<p>Скасування з панелі або email — з кінця оплаченого періоду. Дані можуть бути видалені після ~30 днів, якщо закон не вимагає довше. Повернення передоплати — не автоматичне, крім права відступу та імперативних норм. Домени часто не повертаються після реєстрації.</p>',
                ],
                [
                    'title' => '11. Обмеження відповідальності',
                    'body' => '<p>Послуга з професійною турботою. Не відповідаємо за непрямі збитки, крім умислу/грубої недбалості. Для бізнесу — максимум оплата за 12 місяців. Імперативні права споживачів мають пріоритет.</p>',
                ],
                [
                    'title' => '12. Конфіденційність, cookie, маркетинг',
                    'body' => '<p><a href="' . $c['privacy'] . '">Політика конфіденційності</a> (GDPR, Personopplysningsloven). <a href="' . $c['cookies'] . '">Cookie</a>. Маркетинг — лише за окремою згодою (Markedsføringsloven § 15) з можливістю відписки.</p>',
                ],
                [
                    'title' => '13. Інтелектуальна власність платформи',
                    'body' => '<p>ПЗ, дизайн і документація ' . $c['brand'] . ' — наша (або ліцензіарів) власність. Обмежене право користування на період підписки.</p>',
                ],
                [
                    'title' => '14. Форс-мажор',
                    'body' => '<p>Не відповідаємо за затримки через обставини поза розумним контролем, за умови повідомлення та розумних заходів зменшення шкоди.</p>',
                ],
                [
                    'title' => '15. Право і спори',
                    'body' => '<p>Застосовується <strong>право Норвегії</strong>. Бізнес: Oslo tingrett. Споживачі Норвегії: Forbrukerrådet / суди. Споживачі ЄС/ЄЕЗ — також у країні проживання. ODR: <a href="https://ec.europa.eu/consumers/odr" rel="noopener noreferrer">ec.europa.eu/consumers/odr</a>. Скарги з ПД: <a href="https://www.datatilsynet.no" rel="noopener noreferrer">Datatilsynet</a> або орган вашої країни ЄС/ЄЕЗ.</p>',
                ],
                [
                    'title' => '16. Зміни',
                    'body' => '<p>Суттєві зміни — мінімум за 30 днів. Актуальна версія: <a href="' . $c['terms'] . '">' . $c['terms'] . '</a>.</p>',
                ],
            ],
            'contact_title' => 'Питання щодо умов?',
            'contact_text' => 'Напишіть на email — зазвичай відповідаємо протягом 5 робочих днів.',
        ],
    ];
    return $all[$lang] ?? $all['en'];
}

/** @return array<string, mixed> */
function hs_legal_privacy_doc(string $lang): array
{
    $c = hs_legal_ctx($lang);
    $all = [
        'no' => [
            'title' => 'Personvernerklæring',
            'last_updated' => 'Sist oppdatert: 16. juli 2026',
            'intro' => 'Denne erklæringen beskriver hvordan <strong>' . $c['brand'] . '</strong> (' . $c['domain'] . ') behandler personopplysninger når du besøker nettsiden, registrerer deg, bruker kundepanel eller kontakter support. Vi følger <strong>EUs personvernforordning (GDPR)</strong>, norsk <strong>Personopplysningsloven</strong>, <strong>Ekomloven</strong> (cookies/elektronisk kommunikasjon), bokføringsregler og veiledning fra <a href="https://www.datatilsynet.no" rel="noopener noreferrer">Datatilsynet</a>. Se også <a href="' . $c['cookies'] . '">cookie-erklæringen</a> og <a href="' . $c['terms'] . '">vilkårene</a>.',
            'sections' => [
                [
                    'title' => '1. Behandlingsansvarlig',
                    'body' => '<p><strong>Ruslan Bilohash</strong><br>Drammen, Norge<br>E-post: <a href="mailto:' . $c['email'] . '">' . $c['email'] . '</a><br>Telefon: <a href="tel:+4746255885">+47 462 55 885</a></p>'
                        . '<p>Vi har ikke utpekt personvernombud (DPO) etter GDPR art. 37, da virksomhetens art og omfang ikke utløser plikt. Alle personvernhenvendelser rettes til kontaktadressen over. Svarfrist: normalt innen 30 dager (GDPR art. 12).</p>',
                ],
                [
                    'title' => '2. Roller: behandlingsansvarlig vs. databehandler',
                    'body' => '<ul>'
                        . '<li><strong>Behandlingsansvarlig:</strong> for dine kontoopplysninger, faktura, support, nettstedsbesøk hos oss, sikkerhetslogger og markedsføringssamtykke.</li>'
                        . '<li><strong>Databehandler:</strong> for personopplysninger som du (kunden) lagrer på hostingen om dine egne besøkende/brukere (f.eks. i CMS, skjemaer, e-post). Du er da behandlingsansvarlig og må ha eget personverngrunnlag og erklæring på ditt nettsted. Vi behandler slike data kun på dine instruksjoner for å levere hostingen (GDPR art. 28).</li></ul>',
                ],
                [
                    'title' => '3. Hvem gjelder erklæringen for',
                    'body' => '<p>Besøkende på ' . $c['domain'] . ', registrerte kunder, kontaktpersoner hos bedrifter, brukere av panel, og personer som kontakter oss. Tjenesten rettes primært mot Norge og EØS/EU, men kan brukes internasjonalt.</p>',
                ],
                [
                    'title' => '4. Kategorier av personopplysninger',
                    'body' => '<p><strong>Identitet og kontakt:</strong> navn, e-post, telefon, postadresse, land, språk.</p>'
                        . '<p><strong>Konto:</strong> brukernavn, passord (kun lagret som sikker hash), valgt plan, status, tidspunkter for samtykke.</p>'
                        . '<p><strong>Fakturering:</strong> fakturaer, betalingsstatus, org.nr./MVA der oppgitt, transaksjonsreferanser (kortnummer lagres ikke hos oss når betaling går via PCI-leverandør).</p>'
                        . '<p><strong>Tjenestedata:</strong> domener, DNS-innstillinger, installerte apper, supporthenvendelser og vedlegg.</p>'
                        . '<p><strong>Tekniske data:</strong> IP-adresse, nettleser/OS, tidsstempel, forespørselslogger, sikkerhetshendelser, feilmeldinger.</p>'
                        . '<p><strong>Cookies og lokal lagring:</strong> se <a href="' . $c['cookies'] . '">cookie-erklæring</a>.</p>'
                        . '<p>Vi ber ikke om særlige kategorier (helse, religion, biometri m.m.) for kontoen. Ikke legg inn slike data i support med mindre det er strengt nødvendig.</p>',
                ],
                [
                    'title' => '5. Formål og rettslig grunnlag (GDPR art. 6)',
                    'body' => '<table class="hs-table"><thead><tr><th>Formål</th><th>Grunnlag</th></tr></thead><tbody>'
                        . '<tr><td>Levere hosting, panel, domene, e-post, support</td><td>Avtale art. 6(1)(b)</td></tr>'
                        . '<tr><td>Fakturering, regnskap, skatt</td><td>Rettslig plikt art. 6(1)(c) + Bokføringsloven</td></tr>'
                        . '<tr><td>Sikkerhet, misbruk, DDoS, innbruddsforsøk</td><td>Berettiget interesse art. 6(1)(f)</td></tr>'
                        . '<tr><td>Nødvendige cookies / sesjon / CSRF</td><td>Berettiget interesse / nødvendig for tjeneste</td></tr>'
                        . '<tr><td>Valgfrie funksjonelle cookies</td><td>Samtykke art. 6(1)(a)</td></tr>'
                        . '<tr><td>Markedsføring på e-post</td><td>Samtykke art. 6(1)(a) + Markedsføringsloven § 15</td></tr>'
                        . '</tbody></table>'
                        . '<p>Du kan når som helst trekke tilbake samtykke uten at det påvirker lovligheten av tidligere behandling. Mot berettiget interesse kan du protestere (art. 21).</p>',
                ],
                [
                    'title' => '6. Hvor dataene kommer fra',
                    'body' => '<p>Direkte fra deg (registrering, panel, support), automatisk fra enheten din (logger, cookies), og fra betalings- eller registrarpartnere når du bruker deres tjenester via oss.</p>',
                ],
                [
                    'title' => '7. Mottagere og databehandlere',
                    'body' => '<p>Vi selger ikke personopplysninger. Opplysninger kan deles med:</p><ul>'
                        . '<li><strong>Infrastruktur / registrar</strong> (f.eks. Namecheap / Newfold Digital) — hosting, domener, DNS;</li>'
                        . '<li><strong>Betalingsleverandør</strong> (f.eks. Stripe) — kortbetalinger (PCI DSS);</li>'
                        . '<li><strong>E-post/DNS-leverandører</strong> der tjenesten krever det;</li>'
                        . '<li><strong>CDN</strong> (f.eks. Google Fonts, cdnjs, jsDelivr) — kan motta IP ved lasting av ressurser;</li>'
                        . '<li><strong>Myndigheter</strong> når lov krever det (domstol, politi, Datatilsynet).</li></ul>'
                        . '<p>Der vi bruker databehandlere, inngås databehandleravtale (art. 28) der det er påkrevd.</p>',
                ],
                [
                    'title' => '8. Overføring utenfor EØS',
                    'body' => '<p>Noen leverandører kan behandle data i USA eller andre tredjeland. Overføring skjer da med overføringsgrunnlag etter GDPR kap. V, typisk <strong>EU standardavtaleklausuler (SCC)</strong>, eventuelle tilleggsgarantier, eller tilsvarende lovlige mekanismer. Ta kontakt om du ønsker mer informasjon om aktuelle overføringer.</p>',
                ],
                [
                    'title' => '9. Lagringstid',
                    'body' => '<ul>'
                        . '<li><strong>Konto og tjenestedata:</strong> mens abonnementet er aktivt, deretter sletting/anonymisering innen ca. 30 dager etter oppsigelse (med mindre lov krever lengre).</li>'
                        . '<li><strong>Regnskap/faktura:</strong> inntil 5 år etter regnskapsåret (Bokføringsloven).</li>'
                        . '<li><strong>Support:</strong> inntil 3 år.</li>'
                        . '<li><strong>Server-/sikkerhetslogger:</strong> typisk 30–90 dager, lenger ved aktiv sak.</li>'
                        . '<li><strong>Cookie-samtykke:</strong> inntil 12 måneder (deretter ny forespørsel).</li></ul>',
                ],
                [
                    'title' => '10. Dine rettigheter (GDPR kap. III)',
                    'body' => '<p>Du har rett til: innsyn (art. 15), retting (art. 16), sletting (art. 17), begrensning (art. 18), dataportabilitet (art. 20), protest (art. 21), og tilbakekall av samtykke. For å utøve rettigheter: skriv til <a href="mailto:' . $c['email'] . '">' . $c['email'] . '</a>. Vi kan be om identifikasjon for å unngå misbruk.</p>'
                        . '<p><strong>Klage:</strong> <a href="https://www.datatilsynet.no" rel="noopener noreferrer">Datatilsynet</a> (Norge) eller tilsynsmyndighet i det EØS-landet der du bor eller arbeider. Liste over EU-tilsyn: <a href="https://edpb.europa.eu/about-edpb/about-edpb/members_en" rel="noopener noreferrer">EDPB members</a>.</p>',
                ],
                [
                    'title' => '11. Sikkerhet (GDPR art. 32)',
                    'body' => '<p>Vi bruker blant annet HTTPS/TLS, passordhashing, CSRF-beskyttelse, tilgangskontroll, isolerte kundemapper og sikkerhetsoppdateringer. Ingen metode er 100 % sikker; varsle oss umiddelbart ved mistanke om kompromittert konto.</p>'
                        . '<p><strong>Personvernbrudd:</strong> ved brudd som sannsynligvis medfører risiko for fysiske personers rettigheter, varsler vi Datatilsynet uten ugrunnet opphold og senest innen 72 timer der loven krever det, og berørte personer når risikoen er høy (art. 33–34).</p>',
                ],
                [
                    'title' => '12. Automatiserte avgjørelser',
                    'body' => '<p>Vi tar ikke avgjørelser som utelukkende er basert på automatisert behandling som har rettsvirkning for deg (art. 22), utover tekniske sikkerhetsfiltre (f.eks. rate limiting / misbrukssperre).</p>',
                ],
                [
                    'title' => '13. Barn',
                    'body' => '<p>Tjenesten er ikke rettet mot barn under 16 år uten foresattes samtykke (jf. norsk/EU-regler for informasjonssamfunnstjenester). Vi sletter kontoer som åpenbart er opprettet i strid med dette når vi blir kjent med det.</p>',
                ],
                [
                    'title' => '14. Endringer',
                    'body' => '<p>Vi kan oppdatere erklæringen ved endring i tjeneste eller lov. Ny dato publiseres øverst. Ved vesentlige endringer varsler vi via e-post eller panel der det er rimelig. Historisk gjeldende tekst kan fås på forespørsel.</p>',
                ],
            ],
            'contact_title' => 'Personvernhenvendelser',
            'contact_text' => 'Send e-post for å utøve dine rettigheter. Vi svarer innen 30 dager.',
        ],
        'en' => [
            'title' => 'Privacy Policy',
            'last_updated' => 'Last updated: 16 July 2026',
            'intro' => 'This policy explains how <strong>' . $c['brand'] . '</strong> (' . $c['domain'] . ') processes personal data when you visit the site, register, use the client panel or contact support. We comply with the <strong>EU GDPR</strong>, the Norwegian <strong>Personal Data Act (Personopplysningsloven)</strong>, the <strong>Electronic Communications Act (Ekomloven)</strong> for cookies, accounting rules, and guidance from <a href="https://www.datatilsynet.no" rel="noopener noreferrer">Datatilsynet</a>. See also the <a href="' . $c['cookies'] . '">Cookie Policy</a> and <a href="' . $c['terms'] . '">Terms</a>.',
            'sections' => [
                [
                    'title' => '1. Data controller',
                    'body' => '<p><strong>Ruslan Bilohash</strong><br>Drammen, Norway<br>Email: <a href="mailto:' . $c['email'] . '">' . $c['email'] . '</a><br>Phone: <a href="tel:+4746255885">+47 462 55 885</a></p>'
                        . '<p>We have not appointed a DPO under GDPR Art. 37 (not mandatory for our size/nature). Privacy requests go to the address above. Response: normally within 30 days (Art. 12).</p>',
                ],
                [
                    'title' => '2. Controller vs processor roles',
                    'body' => '<ul>'
                        . '<li><strong>Controller:</strong> your account, billing, support, visits to our website, security logs, marketing consent.</li>'
                        . '<li><strong>Processor:</strong> personal data you store on hosting about your own visitors/users. You remain controller for that data and need your own legal basis and privacy notice. We process it only to provide hosting under your instructions (Art. 28).</li></ul>',
                ],
                [
                    'title' => '3. Who this covers',
                    'body' => '<p>Site visitors, registered customers, business contacts, panel users and anyone who contacts us. Primarily Norway and the EU/EEA, also international use.</p>',
                ],
                [
                    'title' => '4. Categories of data',
                    'body' => '<p><strong>Identity/contact:</strong> name, email, phone, address, country, language.</p>'
                        . '<p><strong>Account:</strong> username, password hash, plan, status, consent timestamps.</p>'
                        . '<p><strong>Billing:</strong> invoices, payment status, org/VAT numbers where provided, transaction references (card numbers are not stored by us when using a PCI payment provider).</p>'
                        . '<p><strong>Service data:</strong> domains, DNS, installed apps, support messages.</p>'
                        . '<p><strong>Technical:</strong> IP, browser/OS, timestamps, server and security logs.</p>'
                        . '<p><strong>Cookies:</strong> see <a href="' . $c['cookies'] . '">Cookie Policy</a>.</p>'
                        . '<p>We do not request special-category data for the account. Avoid sending health/religion/biometric data in support unless strictly necessary.</p>',
                ],
                [
                    'title' => '5. Purposes and legal bases (GDPR Art. 6)',
                    'body' => '<ul>'
                        . '<li>Deliver hosting, panel, domains, email, support — <strong>contract</strong> Art. 6(1)(b)</li>'
                        . '<li>Invoicing, accounting, tax — <strong>legal obligation</strong> Art. 6(1)(c)</li>'
                        . '<li>Security, abuse prevention — <strong>legitimate interests</strong> Art. 6(1)(f)</li>'
                        . '<li>Essential cookies/session/CSRF — necessary for the service / legitimate interests</li>'
                        . '<li>Optional functional cookies — <strong>consent</strong> Art. 6(1)(a)</li>'
                        . '<li>Marketing email — <strong>consent</strong> Art. 6(1)(a) + Norwegian Marketing Control Act § 15</li></ul>'
                        . '<p>You may withdraw consent at any time. You may object to processing based on legitimate interests (Art. 21).</p>',
                ],
                [
                    'title' => '6. Sources',
                    'body' => '<p>From you (registration, panel, support), automatically from your device (logs, cookies), and from payment/registrar partners when you use their services through us.</p>',
                ],
                [
                    'title' => '7. Recipients and processors',
                    'body' => '<p>We do not sell personal data. Recipients may include infrastructure/registrar providers (e.g. Namecheap/Newfold), payment providers (e.g. Stripe), email/DNS providers as needed, CDNs (Google Fonts, cdnjs, jsDelivr — may receive IP), and authorities when required by law. Processors are bound by Art. 28 agreements where required.</p>',
                ],
                [
                    'title' => '8. Transfers outside the EEA',
                    'body' => '<p>Some providers may process data in the US or other third countries. Transfers rely on GDPR Chapter V mechanisms, typically <strong>EU Standard Contractual Clauses (SCCs)</strong> and related safeguards. Contact us for more detail on current transfers.</p>',
                ],
                [
                    'title' => '9. Retention',
                    'body' => '<ul>'
                        . '<li>Account/service data: during subscription, then deletion/anonymisation within ~30 days after termination (unless law requires longer)</li>'
                        . '<li>Accounting/invoices: up to 5 years (Norwegian Bookkeeping Act)</li>'
                        . '<li>Support: up to 3 years</li>'
                        . '<li>Server/security logs: typically 30–90 days</li>'
                        . '<li>Cookie consent: up to 12 months</li></ul>',
                ],
                [
                    'title' => '10. Your rights (GDPR Chapter III)',
                    'body' => '<p>Access, rectification, erasure, restriction, portability, objection, withdraw consent. Email <a href="mailto:' . $c['email'] . '">' . $c['email'] . '</a>. We may verify identity. Complaints: <a href="https://www.datatilsynet.no" rel="noopener noreferrer">Datatilsynet</a> (Norway) or your EU/EEA supervisory authority (<a href="https://edpb.europa.eu/about-edpb/about-edpb/members_en" rel="noopener noreferrer">EDPB list</a>).</p>',
                ],
                [
                    'title' => '11. Security (Art. 32)',
                    'body' => '<p>HTTPS/TLS, password hashing, CSRF protection, access control, isolated customer folders, security updates. Breach notification to Datatilsynet within 72 hours when required, and to individuals when risk is high (Art. 33–34).</p>',
                ],
                [
                    'title' => '12. Automated decisions',
                    'body' => '<p>We do not make solely automated decisions with legal effects under Art. 22, beyond technical security filters (rate limits, abuse blocks).</p>',
                ],
                [
                    'title' => '13. Children',
                    'body' => '<p>Not directed at children under 16 without parental consent. Accounts clearly created in breach will be deleted when we become aware.</p>',
                ],
                [
                    'title' => '14. Changes',
                    'body' => '<p>We may update this policy when the service or law changes. The new date is shown at the top. Material changes may be notified by email or panel.</p>',
                ],
            ],
            'contact_title' => 'Privacy enquiries',
            'contact_text' => 'Email us to exercise your rights — we reply within 30 days.',
        ],
        'uk' => [
            'title' => 'Політика конфіденційності',
            'last_updated' => 'Останнє оновлення: 16 липня 2026',
            'intro' => 'Ця політика описує, як <strong>' . $c['brand'] . '</strong> (' . $c['domain'] . ') обробляє персональні дані під час відвідування сайту, реєстрації, роботи в панелі та звернень до підтримки. Дотримуємось <strong>GDPR</strong>, норвезького <strong>Personopplysningsloven</strong>, <strong>Ekomloven</strong> (cookie), правил бухобліку та рекомендацій <a href="https://www.datatilsynet.no" rel="noopener noreferrer">Datatilsynet</a>. Див. також <a href="' . $c['cookies'] . '">Політику cookie</a> та <a href="' . $c['terms'] . '">Умови</a>.',
            'sections' => [
                [
                    'title' => '1. Контролер даних',
                    'body' => '<p><strong>Ruslan Bilohash</strong><br>Drammen, Норвегія<br>Email: <a href="mailto:' . $c['email'] . '">' . $c['email'] . '</a><br>Тел.: <a href="tel:+4746255885">+47 462 55 885</a></p>'
                        . '<p>DPO за ст. 37 GDPR не призначено (не обов’язково для нашого масштабу). Запити — на email вище. Відповідь: зазвичай протягом 30 днів (ст. 12).</p>',
                ],
                [
                    'title' => '2. Ролі: контролер і обробник',
                    'body' => '<ul>'
                        . '<li><strong>Контролер:</strong> дані акаунта, оплати, підтримки, відвідувань нашого сайту, логи безпеки, маркетингова згода.</li>'
                        . '<li><strong>Обробник:</strong> персональні дані, які ви зберігаєте на хостингу про своїх відвідувачів. Ви залишаєтесь контролером і потрібна власна політика/підстава. Ми обробляємо лише для надання хостингу за вашими інструкціями (ст. 28).</li></ul>',
                ],
                [
                    'title' => '3. Кому це стосується',
                    'body' => '<p>Відвідувачі сайту, зареєстровані клієнти, контактні особи компаній, користувачі панелі. Переважно Норвегія та ЄС/ЄЕЗ, також міжнародне використання.</p>',
                ],
                [
                    'title' => '4. Категорії даних',
                    'body' => '<p><strong>Ідентифікація/контакт:</strong> ім’я, email, телефон, адреса, країна, мова.</p>'
                        . '<p><strong>Акаунт:</strong> логін, хеш пароля, тариф, статус, мітки часу згоди.</p>'
                        . '<p><strong>Оплата:</strong> рахунки, статус платежів, орг./ПДВ (якщо вказано); номери карток у нас не зберігаються (PCI-провайдер).</p>'
                        . '<p><strong>Сервісні:</strong> домени, DNS, додатки, звернення підтримки.</p>'
                        . '<p><strong>Технічні:</strong> IP, браузер/ОС, логи сервера та безпеки.</p>'
                        . '<p><strong>Cookie:</strong> <a href="' . $c['cookies'] . '">Політика cookie</a>.</p>',
                ],
                [
                    'title' => '5. Цілі та правові підстави (ст. 6 GDPR)',
                    'body' => '<ul>'
                        . '<li>Надання хостингу, панелі, доменів, пошти, підтримки — <strong>договір</strong> 6(1)(b)</li>'
                        . '<li>Рахунки, облік, податки — <strong>юридичний обов’язок</strong> 6(1)(c)</li>'
                        . '<li>Безпека, запобігання зловживанням — <strong>законний інтерес</strong> 6(1)(f)</li>'
                        . '<li>Необхідні cookie/сесія/CSRF — необхідність послуги / законний інтерес</li>'
                        . '<li>Опційні функціональні cookie — <strong>згода</strong> 6(1)(a)</li>'
                        . '<li>Маркетинг email — <strong>згода</strong> 6(1)(a) + Markedsføringsloven § 15</li></ul>',
                ],
                [
                    'title' => '6. Джерела даних',
                    'body' => '<p>Від вас (реєстрація, панель, підтримка), автоматично з пристрою (логи, cookie), від платіжних/реєстраторських партнерів.</p>',
                ],
                [
                    'title' => '7. Отримувачі та обробники',
                    'body' => '<p>Дані не продаємо. Можливі отримувачі: інфраструктура/реєстратор (Namecheap/Newfold), платіжні сервіси (Stripe), CDN (Google Fonts, cdnjs, jsDelivr), органи влади за законом. DPA за ст. 28 — де потрібно.</p>',
                ],
                [
                    'title' => '8. Передача за межі ЄЕЗ',
                    'body' => '<p>Деякі постачальники можуть обробляти дані в США чи інших країнах. Підстава — глава V GDPR, зазвичай <strong>SCC (стандартні договірні положення ЄС)</strong>.</p>',
                ],
                [
                    'title' => '9. Строки зберігання',
                    'body' => '<ul>'
                        . '<li>Акаунт/сервіс: під час підписки, потім видалення ~30 днів</li>'
                        . '<li>Бухоблік/рахунки: до 5 років</li>'
                        . '<li>Підтримка: до 3 років</li>'
                        . '<li>Логи: 30–90 днів</li>'
                        . '<li>Згода cookie: до 12 місяців</li></ul>',
                ],
                [
                    'title' => '10. Ваші права (глава III GDPR)',
                    'body' => '<p>Доступ, виправлення, видалення, обмеження, переносимість, заперечення, відкликання згоди. Email: <a href="mailto:' . $c['email'] . '">' . $c['email'] . '</a>. Скарга: <a href="https://www.datatilsynet.no" rel="noopener noreferrer">Datatilsynet</a> або наглядовий орган ЄС/ЄЕЗ (<a href="https://edpb.europa.eu/about-edpb/about-edpb/members_en" rel="noopener noreferrer">список EDPB</a>).</p>',
                ],
                [
                    'title' => '11. Безпека (ст. 32)',
                    'body' => '<p>HTTPS/TLS, хешування паролів, CSRF, контроль доступу, ізольовані папки. Повідомлення про інцидент — Datatilsynet протягом 72 год, коли вимагає закон; особам — при високому ризику (ст. 33–34).</p>',
                ],
                [
                    'title' => '12. Автоматизовані рішення',
                    'body' => '<p>Не приймаємо виключно автоматизованих рішень зі ст. 22, окрім технічних фільтрів безпеки.</p>',
                ],
                [
                    'title' => '13. Діти',
                    'body' => '<p>Не для дітей до 16 років без згоди батьків.</p>',
                ],
                [
                    'title' => '14. Зміни',
                    'body' => '<p>Оновлюємо при зміні послуги чи закону. Нова дата — зверху. Суттєві зміни можемо повідомити email/панеллю.</p>',
                ],
            ],
            'contact_title' => 'З питань конфіденційності',
            'contact_text' => 'Напишіть на email — відповідаємо протягом 30 днів.',
        ],
    ];
    return $all[$lang] ?? $all['en'];
}

/** @return array<string, mixed> */
function hs_legal_cookies_doc(string $lang): array
{
    $c = hs_legal_ctx($lang);
    $rows = hs_legal_cookie_rows($lang);
    $all = [
        'no' => [
            'title' => 'Cookie-erklæring',
            'last_updated' => 'Sist oppdatert: 16. juli 2026',
            'intro' => 'Denne erklæringen forklarer bruk av informasjonskapsler (cookies) og lokal lagring på <strong>' . $c['domain'] . '</strong> og i kundepanel, i tråd med GDPR, Ekomloven og ePrivacy-prinsipper. <strong>Nødvendige</strong> cookies brukes uten samtykke (nødvendige for tjenesten / berettiget interesse). <strong>Valgfrie</strong> krever samtykke. Vi bruker ikke tredjeparts reklame- eller analyse-cookies (f.eks. Google Analytics, Meta Pixel) per dags dato.',
            'sections' => [
                [
                    'title' => '1. Hva er cookies og lokal lagring?',
                    'body' => '<p>Små tekstfiler eller data i nettleseren som husker innstillinger, sesjon, sikkerhetstokens eller samtykkevalg. Noe lagres som cookie, noe i localStorage/sessionStorage.</p>',
                ],
                [
                    'title' => '2. Rettslig grunnlag',
                    'body' => '<ul>'
                        . '<li><strong>Nødvendige:</strong> GDPR art. 6(1)(b)/(f) — innlogging, CSRF, lastbalanse, lagring av samtykkevalg.</li>'
                        . '<li><strong>Funksjonelle (valgfrie):</strong> art. 6(1)(a) — f.eks. UI-preferanser som ikke er strengt nødvendige.</li></ul>'
                        . '<p>Etter ePrivacy / Ekomloven krever ikke-nødvendige cookies informert samtykke før lagring.</p>',
                ],
                [
                    'title' => '3. Slik administrerer du valg',
                    'body' => '<p>Ved første besøk vises et samtykkebanner. Valget lagres inntil 12 måneder. På denne siden kan du <strong>endre samtykke</strong>, velge <strong>kun nødvendige</strong>, eller <strong>slette lesbare cookies og nullstille samtykke</strong> (se panelet under). Du kan også slette nettleserdata eller nøkkelen <code>hs_cookie_consent</code> i localStorage.</p>',
                ],
                [
                    'title' => '4. Tredjepartsressurser',
                    'body' => '<p>Skrifter og ikoner (f.eks. Google Fonts, Font Awesome, jsDelivr/cdnjs) lastes fra CDN og kan motta IP-adressen din. Dette er teknisk nødvendig for visning. Vi setter ikke egne sporingscookies via dem. Se også <a href="' . $c['privacy'] . '">personvernerklæringen</a> om overføringer.</p>',
                ],
                [
                    'title' => '5. Mer informasjon',
                    'body' => '<p><a href="' . $c['privacy'] . '">Personvernerklæring</a> · <a href="' . $c['terms'] . '">Vilkår</a> · <a href="mailto:' . $c['email'] . '">' . $c['email'] . '</a></p>',
                ],
            ],
            'table_name' => 'Navn', 'table_type' => 'Type', 'table_purpose' => 'Formål', 'table_duration' => 'Varighet',
            'cookie_rows' => $rows,
        ],
        'en' => [
            'title' => 'Cookie Policy',
            'last_updated' => 'Last updated: 16 July 2026',
            'intro' => 'This policy explains cookies and local storage on <strong>' . $c['domain'] . '</strong> and the client panel under GDPR, the Norwegian Ekomloven and ePrivacy principles. <strong>Essential</strong> cookies run without consent. <strong>Optional</strong> cookies require consent. We do not currently use third-party advertising or analytics cookies (e.g. Google Analytics, Meta Pixel).',
            'sections' => [
                [
                    'title' => '1. What are cookies?',
                    'body' => '<p>Small files or browser storage used for settings, sessions, security tokens or consent preferences (cookies or localStorage/sessionStorage).</p>',
                ],
                [
                    'title' => '2. Legal basis',
                    'body' => '<ul>'
                        . '<li><strong>Essential:</strong> GDPR Art. 6(1)(b)/(f) — login, CSRF, storing consent choice.</li>'
                        . '<li><strong>Functional (optional):</strong> Art. 6(1)(a) — non-essential UI preferences.</li></ul>'
                        . '<p>Under ePrivacy / Ekomloven, non-essential cookies require informed consent before storage.</p>',
                ],
                [
                    'title' => '3. Managing your choice',
                    'body' => '<p>A consent banner appears on first visit. Choice is stored up to 12 months. On this page you can <strong>change preferences</strong>, <strong>accept only essential cookies</strong>, or <strong>delete readable cookies and reset consent</strong> (see the management panel below). You can also clear browser data or remove <code>hs_cookie_consent</code> in localStorage.</p>',
                ],
                [
                    'title' => '4. Third-party resources',
                    'body' => '<p>Fonts and icons (e.g. Google Fonts, Font Awesome, jsDelivr/cdnjs) load from CDNs and may receive your IP for display. No advertising tracking cookies. See the <a href="' . $c['privacy'] . '">Privacy Policy</a>.</p>',
                ],
                [
                    'title' => '5. More information',
                    'body' => '<p><a href="' . $c['privacy'] . '">Privacy Policy</a> · <a href="' . $c['terms'] . '">Terms</a> · <a href="mailto:' . $c['email'] . '">' . $c['email'] . '</a></p>',
                ],
            ],
            'table_name' => 'Name', 'table_type' => 'Type', 'table_purpose' => 'Purpose', 'table_duration' => 'Duration',
            'cookie_rows' => $rows,
        ],
        'uk' => [
            'title' => 'Політика cookie',
            'last_updated' => 'Останнє оновлення: 16 липня 2026',
            'intro' => 'Пояснює cookie та локальне сховище на <strong>' . $c['domain'] . '</strong> і в панелі (GDPR, Ekomloven, ePrivacy). <strong>Необхідні</strong> — без згоди. <strong>Опційні</strong> — за згодою. Рекламних/аналітичних cookie третіх сторін (Google Analytics, Meta Pixel) зараз немає.',
            'sections' => [
                [
                    'title' => '1. Що таке cookie?',
                    'body' => '<p>Невеликі файли або сховище браузера для налаштувань, сесії, токенів безпеки чи вибору згоди (cookie або localStorage).</p>',
                ],
                [
                    'title' => '2. Правова підстава',
                    'body' => '<ul>'
                        . '<li><strong>Необхідні:</strong> ст. 6(1)(b)/(f) GDPR — вхід, CSRF, збереження згоди.</li>'
                        . '<li><strong>Функціональні (опційні):</strong> ст. 6(1)(a) — UI-налаштування.</li></ul>',
                ],
                [
                    'title' => '3. Керування вибором',
                    'body' => '<p>Банер при першому візиті. Вибір — до 12 місяців. На цій сторінці можна <strong>змінити згоду</strong>, обрати <strong>лише необхідні cookie</strong> або <strong>видалити читабельні cookie та скинути згоду</strong> (панель керування нижче). Також можна очистити дані браузера або ключ <code>hs_cookie_consent</code> у localStorage.</p>',
                ],
                [
                    'title' => '4. Сторонні ресурси',
                    'body' => '<p>Шрифти/іконки з CDN (Google Fonts, Font Awesome тощо) можуть отримувати IP. Без рекламних cookie. Див. <a href="' . $c['privacy'] . '">Політику конфіденційності</a>.</p>',
                ],
                [
                    'title' => '5. Додатково',
                    'body' => '<p><a href="' . $c['privacy'] . '">Конфіденційність</a> · <a href="' . $c['terms'] . '">Умови</a></p>',
                ],
            ],
            'table_name' => 'Назва', 'table_type' => 'Тип', 'table_purpose' => 'Призначення', 'table_duration' => 'Термін',
            'cookie_rows' => $rows,
        ],
    ];
    return $all[$lang] ?? $all['en'];
}

/**
 * Domain registration policy — Namecheap reseller / ICANN pass-through requirements.
 * Not a substitute for licensed legal advice. Links to official Namecheap legal docs.
 *
 * @return array<string, mixed>
 */
function hs_legal_domains_doc(string $lang): array
{
    $c = hs_legal_ctx($lang);
    $ncLegal = 'https://www.namecheap.com/legal/';
    $ncReg = 'https://www.namecheap.com/legal/domains/registration-agreement/';
    $ncTos = 'https://www.namecheap.com/legal/universal/universal-tos/';
    $ncPriv = 'https://www.namecheap.com/legal/general/privacy-policy/';
    $ncWhois = 'https://www.namecheap.com/legal/domain-privacy/whois-privacy-service-agreement/';
    $icannRights = 'https://www.icann.org/resources/pages/benefits-2013-09-16-en';
    $icannEdu = 'https://www.icann.org/resources/pages/educational-2012-02-25-en';
    $rdp = 'https://www.icann.org/en/contracted-parties/consensus-policies/registration-data-policy';

    $all = [
        'en' => [
            'title' => 'Domain Registration Policy (Namecheap / ICANN)',
            'last_updated' => 'Last updated: 17 July 2026',
            'intro' => 'This policy explains how domain names are registered and managed through <strong>' . $c['brand'] . '</strong> (' . $c['domain'] . ') using the ICANN-accredited registrar <strong>Namecheap</strong> (<a href="https://www.namecheap.com/" rel="noopener noreferrer">namecheap.com</a>). It summarises what the law and ICANN/registrar rules require of you as the domain registrant, and of us as a reseller. This document is part of our legal suite together with the <a href="' . $c['terms'] . '">Terms of Service</a> and <a href="' . $c['privacy'] . '">Privacy Policy</a>. It is not a substitute for independent legal advice.',
            'sections' => [
                [
                    'title' => '1. Who is the registrar and who is the reseller?',
                    'body' => '<p><strong>Registrar:</strong> Namecheap, Inc. (and related Newfold Digital entities) is an <strong>ICANN-accredited domain name registrar</strong>. The domain is registered in the registry through Namecheap’s systems.</p>'
                        . '<p><strong>Reseller / intermediary:</strong> ' . $c['brand'] . ' sells hosting and may order domains for you via Namecheap’s API or reseller tools. We are <strong>not</strong> an independent ICANN registrar. Contractually, your registration is subject to Namecheap’s agreements as well as these terms.</p>'
                        . '<p>Official Namecheap legal centre: <a href="' . $ncLegal . '" rel="noopener noreferrer">' . $ncLegal . '</a>.</p>',
                ],
                [
                    'title' => '2. Agreements you accept when you order a domain',
                    'body' => '<p>By purchasing or renewing a domain through our website or panel, you confirm that you have read and accept (pass-through):</p><ul>'
                        . '<li><a href="' . $ncReg . '" rel="noopener noreferrer">Namecheap Domain Registration Agreement</a> — core contract for registration and use of the domain name;</li>'
                        . '<li><a href="' . $ncTos . '" rel="noopener noreferrer">Namecheap Universal Terms of Service</a> — use of Namecheap systems, platforms and related services;</li>'
                        . '<li><a href="' . $ncPriv . '" rel="noopener noreferrer">Namecheap Privacy Policy</a> — how Namecheap processes registration and account data;</li>'
                        . '<li>If WHOIS privacy / proxy is enabled: <a href="' . $ncWhois . '" rel="noopener noreferrer">WHOIS Privacy Service Agreement</a>;</li>'
                        . '<li>ICANN policies and the rules of the specific top-level domain (TLD) registry (e.g. Verisign for .com, Norid for .no).</li></ul>'
                        . '<p>If Namecheap’s terms conflict with marketing text on our site, <strong>Namecheap’s and the registry’s rules prevail</strong> for the registration itself. Our Terms cover the commercial relationship with ' . $c['brand'] . ' (payment, support, hosting).</p>',
                ],
                [
                    'title' => '3. What the law and ICANN require of the registrant (you)',
                    'body' => '<p>As the Registered Name Holder you must, among other things:</p><ul>'
                        . '<li><strong>Provide accurate registration data</strong> (name, postal address, email, phone, and organisation data where applicable) and keep it up to date — ICANN Registration Data Policy / RDDS and registrar agreements;</li>'
                        . '<li><strong>Respond to verification emails</strong> from the registrar (e.g. WDRP / registrant email verification). Failure may lead to suspension of the domain;</li>'
                        . '<li><strong>Not use the domain for illegal activity</strong> under applicable law, ICANN policies or Namecheap’s Acceptable Use rules;</li>'
                        . '<li><strong>Pay renewal fees on time</strong> — non-payment may result in expiry, redemption period fees, or deletion;</li>'
                        . '<li><strong>Respect third-party rights</strong> (trademarks, personality rights, etc.). Domain disputes may follow UDRP or local court procedures;</li>'
                        . '<li>For country-code TLDs (e.g. <strong>.no</strong>): additional local rules (Norid) may apply, including residency or organisation requirements.</li></ul>'
                        . '<p>ICANN educational materials and registrant benefits: <a href="' . $icannRights . '" rel="noopener noreferrer">Registrant rights and responsibilities</a> · <a href="' . $icannEdu . '" rel="noopener noreferrer">ICANN education</a> · <a href="' . $rdp . '" rel="noopener noreferrer">Registration Data Policy</a>.</p>',
                ],
                [
                    'title' => '4. Data we collect and transfer to Namecheap (GDPR)',
                    'body' => '<p>To register a domain we must collect and transmit to the registrar the data required for registration (registrant, admin/tech contacts as applicable). Legal bases typically include <strong>performance of a contract</strong> (GDPR Art. 6(1)(b)) and, where applicable, legal obligations related to domain systems.</p>'
                        . '<p>Namecheap may process data in the <strong>United States</strong> or other countries. Transfers rely on GDPR Chapter V mechanisms (e.g. Standard Contractual Clauses) under Namecheap’s privacy programme. Details: Namecheap Privacy Policy and our <a href="' . $c['privacy'] . '">Privacy Policy</a>.</p>'
                        . '<p>Public WHOIS/RDAP display is limited under current ICANN/GDPR practice; the registrar still holds full registration data and may disclose it under legal process or ICANN policy.</p>',
                ],
                [
                    'title' => '5. Privacy / proxy WHOIS (if offered)',
                    'body' => '<p>If WHOIS privacy or proxy service is included or purchased, your public contact data may be replaced by the privacy provider’s data, subject to the <a href="' . $ncWhois . '" rel="noopener noreferrer">WHOIS Privacy Service Agreement</a>. Privacy does not remove your duty to provide true data to the registrar, nor does it protect against court orders, UDRP or mandatory disclosure.</p>',
                ],
                [
                    'title' => '6. Pricing, renewal, expiry and refunds',
                    'body' => '<p>Domain prices shown at checkout include our margin and the registrar’s cost for the selected period (usually 1 year unless stated otherwise). Renewal prices may differ from first-year promotional prices.</p>'
                        . '<p><strong>Automatic renewal:</strong> if enabled, we attempt to charge and renew before expiry. You must keep payment methods and contact email valid.</p>'
                        . '<p><strong>Expiry:</strong> after expiry the domain may enter grace/redemption periods with extra fees set by the registry/registrar, then be deleted and become available to others.</p>'
                        . '<p><strong>Refunds:</strong> once a domain is successfully registered or renewed at the registry, fees are generally <strong>non-refundable</strong> under registrar rules. EU/EEA consumers may still have a 14-day withdrawal right for distance contracts unless an applicable exception for digital services fully performed with prior consent applies — we inform you at checkout. Domains already registered at the registry are typically hard to reverse; contact ' . $c['email'] . ' immediately if you believe a mistake was made.</p>',
                ],
                [
                    'title' => '7. Transfers, ownership changes and locks',
                    'body' => '<p>Transfers between registrars follow ICANN Transfer Policy (auth code / EPP, locks, confirmation emails). You must unlock the domain and approve transfer emails. We can assist via the panel/support but cannot force a registry transfer if the losing registrar or registry blocks it for policy reasons.</p>'
                        . '<p>Pushing ownership to another person requires correct data for the new registrant. Fraudulent transfers may be reversed under registrar/ICANN procedures.</p>',
                ],
                [
                    'title' => '8. DNS and hosting relationship',
                    'body' => '<p>Buying a domain does not automatically include website content. Hosting is a separate service under our Terms. You may point DNS to our nameservers or external hosts. Misconfigured DNS is your responsibility unless caused by a documented error on our side.</p>',
                ],
                [
                    'title' => '9. Abuse, illegal use and suspension',
                    'body' => '<p>Namecheap and ' . $c['brand'] . ' may suspend or take down domains used for phishing, malware, spam, CSAM, fraud or other illegal activity, or pursuant to court orders / ICANN policies. We may act urgently without prior notice where required for security or law enforcement cooperation.</p>',
                ],
                [
                    'title' => '10. Disputes (UDRP and courts)',
                    'body' => '<p>Trademark-related domain disputes may be resolved under the <strong>Uniform Domain-Name Dispute-Resolution Policy (UDRP)</strong> or court proceedings. Our commercial disputes with you about fees or hosting remain governed by Norwegian law as set out in the Terms, without limiting mandatory consumer rights in your country of residence in the EU/EEA.</p>',
                ],
                [
                    'title' => '11. Official Namecheap documents (must-read links)',
                    'body' => '<ul>'
                        . '<li><a href="' . $ncLegal . '" rel="noopener noreferrer">Namecheap Legal hub</a></li>'
                        . '<li><a href="' . $ncReg . '" rel="noopener noreferrer">Domain Registration Agreement</a></li>'
                        . '<li><a href="' . $ncTos . '" rel="noopener noreferrer">Universal Terms of Service</a></li>'
                        . '<li><a href="' . $ncPriv . '" rel="noopener noreferrer">Namecheap Privacy Policy</a></li>'
                        . '<li><a href="' . $ncWhois . '" rel="noopener noreferrer">WHOIS Privacy Service Agreement</a></li>'
                        . '<li><a href="https://www.namecheap.com/" rel="noopener noreferrer">namecheap.com</a></li></ul>'
                        . '<p>Namecheap may update its legal documents; the version published on namecheap.com applies to registrations processed after the update.</p>',
                ],
                [
                    'title' => '12. Contact',
                    'body' => '<p>Questions about domain orders placed through ' . $c['brand'] . ': <a href="mailto:' . $c['email'] . '">' . $c['email'] . '</a>.</p>'
                        . '<p>Registrar-level account or policy issues may require escalation to Namecheap support in accordance with their procedures.</p>',
                ],
            ],
            'contact_title' => 'Domain registration questions?',
            'contact_text' => 'Email support for orders via our panel. For Namecheap’s own policies, use the official legal links above.',
        ],
        'no' => [
            'title' => 'Policy for domeneregistrering (Namecheap / ICANN)',
            'last_updated' => 'Sist oppdatert: 17. juli 2026',
            'intro' => 'Denne policyen forklarer hvordan domenenavn registreres og administreres via <strong>' . $c['brand'] . '</strong> (' . $c['domain'] . ') gjennom ICANN-akkreditert registrar <strong>Namecheap</strong> (<a href="https://www.namecheap.com/" rel="noopener noreferrer">namecheap.com</a>). Den oppsummerer lov- og ICANN-/registrarplikter for deg som registrant og for oss som forhandler. Del av vårt juridiske sett sammen med <a href="' . $c['terms'] . '">vilkårene</a> og <a href="' . $c['privacy'] . '">personvernerklæringen</a>. Ikke erstatning for juridisk rådgivning.',
            'sections' => [
                [
                    'title' => '1. Hvem er registrar og hvem er forhandler?',
                    'body' => '<p><strong>Registrar:</strong> Namecheap, Inc. (og tilknyttede Newfold Digital-selskaper) er <strong>ICANN-akkreditert domeneregistrar</strong>. Domenet registreres i registry via Namecheaps systemer.</p>'
                        . '<p><strong>Forhandler:</strong> ' . $c['brand'] . ' selger hosting og kan bestille domener via Namecheap API/forhandlerverktøy. Vi er <strong>ikke</strong> selvstendig ICANN-registrar. Registreringen er underlagt Namecheaps avtaler i tillegg til våre vilkår.</p>'
                        . '<p>Namecheap juridisk hub: <a href="' . $ncLegal . '" rel="noopener noreferrer">' . $ncLegal . '</a>.</p>',
                ],
                [
                    'title' => '2. Avtaler du godtar ved domenekjøp',
                    'body' => '<p>Ved kjøp eller fornyelse via oss bekrefter du (pass-through) blant annet:</p><ul>'
                        . '<li><a href="' . $ncReg . '" rel="noopener noreferrer">Namecheap Domain Registration Agreement</a>;</li>'
                        . '<li><a href="' . $ncTos . '" rel="noopener noreferrer">Universal Terms of Service</a>;</li>'
                        . '<li><a href="' . $ncPriv . '" rel="noopener noreferrer">Namecheap Privacy Policy</a>;</li>'
                        . '<li>ved WHOIS-privacy: <a href="' . $ncWhois . '" rel="noopener noreferrer">WHOIS Privacy Service Agreement</a>;</li>'
                        . '<li>ICANN-policyer og aktuelle TLD-/registry-regler (f.eks. .no / Norid).</li></ul>'
                        . '<p>Ved konflikt om selve registreringen gjelder Namecheap/registry foran markedsføringstekst. Våre vilkår regulerer handelsforholdet med ' . $c['brand'] . '.</p>',
                ],
                [
                    'title' => '3. Lov- og ICANN-krav til deg som registrant',
                    'body' => '<p>Du må blant annet:</p><ul>'
                        . '<li>oppgi <strong>korrekte registreringsdata</strong> og holde dem oppdatert (ICANN Registration Data Policy / RDDS);</li>'
                        . '<li><strong>svare på verifiserings-e-post</strong> fra registrar (manglende svar kan gi suspensjon);</li>'
                        . '<li>ikke bruke domenet til <strong>ulovlig aktivitet</strong>;</li>'
                        . '<li><strong>betale fornyelse i tide</strong>;</li>'
                        . '<li>respektere andres rettigheter (varemerke m.m.; UDRP/domstol);</li>'
                        . '<li>for landskoder (f.eks. <strong>.no</strong>): følge Norid og lokale krav.</li></ul>'
                        . '<p>ICANN: <a href="' . $icannRights . '" rel="noopener noreferrer">Registrant rights</a> · <a href="' . $rdp . '" rel="noopener noreferrer">Registration Data Policy</a>.</p>',
                ],
                [
                    'title' => '4. Personopplysninger til Namecheap (GDPR)',
                    'body' => '<p>Vi samler inn og overfører data som kreves for registrering (registrant/kontakt). Grunnlag: typisk <strong>oppfyllelse av avtale</strong> (GDPR art. 6(1)(b)). Namecheap kan behandle data i USA; overføring etter GDPR kap. V (f.eks. SCC). Se Namecheaps personvernpolicy og vår <a href="' . $c['privacy'] . '">personvernerklæring</a>.</p>',
                ],
                [
                    'title' => '5. WHOIS-privacy (hvis tilbudt)',
                    'body' => '<p>Privacy/proxy erstatter ikke plikten til å gi sanne data til registrar, og beskytter ikke mot rettslige pålegg eller UDRP. Se <a href="' . $ncWhois . '" rel="noopener noreferrer">WHOIS Privacy Service Agreement</a>.</p>',
                ],
                [
                    'title' => '6. Pris, fornyelse, utløp og refusjon',
                    'body' => '<p>Priser ved kasse inkluderer registrarens kostnad og vår margin. Fornyelsespris kan avvike fra introduksjonspris. Automatisk fornyelse krever gyldig betaling og e-post. Etter utløp: grace/redemption med mulige ekstra gebyrer, deretter sletting.</p>'
                        . '<p><strong>Refusjon:</strong> vellykket registrering/fornyelse i registry er som hovedregel <strong>ikke-refunderbar</strong> hos registrar. Forbrukere i EØS/EU kan ha 14 dagers angrerett med mindre unntak for digitale tjenester gjelder — informeres ved kasse. Kontakt ' . $c['email'] . ' ved feilbestilling.</p>',
                ],
                [
                    'title' => '7. Flytting, eierskifte og lås',
                    'body' => '<p>Transfer følger ICANN Transfer Policy (auth-kode, lås, bekreftelses-e-post). Vi bistår, men kan ikke tvinge gjennom overføring mot registrar/registry-blokk.</p>',
                ],
                [
                    'title' => '8. DNS og hosting',
                    'body' => '<p>Domene er separat fra hosting. Du kan peke DNS til våre eller eksterne navneservere. Feil DNS er ditt ansvar med mindre det skyldes dokumentert feil hos oss.</p>',
                ],
                [
                    'title' => '9. Misbruk og stenging',
                    'body' => '<p>Namecheap og ' . $c['brand'] . ' kan stenge domener brukt til phishing, malware, spam, ulovlig innhold eller etter pålegg. Akutt stenging kan skje uten forhåndsvarsel ved alvorlig risiko.</p>',
                ],
                [
                    'title' => '10. Tvister',
                    'body' => '<p>Varemerketvister kan følge <strong>UDRP</strong> eller domstol. Kommersielle tvister om betaling/hosting følger norsk rett som i vilkårene, uten å begrense ufravikelige forbrukerrettigheter i EØS/EU.</p>',
                ],
                [
                    'title' => '11. Offisielle Namecheap-dokumenter',
                    'body' => '<ul>'
                        . '<li><a href="' . $ncLegal . '" rel="noopener noreferrer">Legal hub</a></li>'
                        . '<li><a href="' . $ncReg . '" rel="noopener noreferrer">Registration Agreement</a></li>'
                        . '<li><a href="' . $ncTos . '" rel="noopener noreferrer">Universal TOS</a></li>'
                        . '<li><a href="' . $ncPriv . '" rel="noopener noreferrer">Privacy Policy</a></li>'
                        . '<li><a href="' . $ncWhois . '" rel="noopener noreferrer">WHOIS Privacy Agreement</a></li></ul>',
                ],
                [
                    'title' => '12. Kontakt',
                    'body' => '<p>Domenebestillinger via oss: <a href="mailto:' . $c['email'] . '">' . $c['email'] . '</a>. Registrar-spørsmål kan måtte eskaleres til Namecheap.</p>',
                ],
            ],
            'contact_title' => 'Spørsmål om domeneregistrering?',
            'contact_text' => 'Kontakt support for bestillinger via panelet. For Namecheaps egne vilkår: se lenkene over.',
        ],
        'uk' => [
            'title' => 'Політика реєстрації доменів (Namecheap / ICANN)',
            'last_updated' => 'Останнє оновлення: 17 липня 2026',
            'intro' => 'Ця політика пояснює, як доменні імена реєструються через <strong>' . $c['brand'] . '</strong> (' . $c['domain'] . ') за допомогою ICANN-акредитованого реєстратора <strong>Namecheap</strong> (<a href="https://www.namecheap.com/" rel="noopener noreferrer">namecheap.com</a>). Тут — що вимагає закон і правила ICANN/реєстратора від вас як реєстранта та від нас як реселера. Частина правового комплекту разом із <a href="' . $c['terms'] . '">Умовами</a> та <a href="' . $c['privacy'] . '">Політикою конфіденційності</a>. Не замінює консультацію юриста.',
            'sections' => [
                [
                    'title' => '1. Хто реєстратор і хто реселер?',
                    'body' => '<p><strong>Реєстратор:</strong> Namecheap, Inc. (і пов’язані Newfold Digital) — <strong>ICANN-акредитований реєстратор</strong>. Домен реєструється в registry через системи Namecheap.</p>'
                        . '<p><strong>Реселер:</strong> ' . $c['brand'] . ' продає хостинг і може замовляти домени через API Namecheap. Ми <strong>не</strong> самостійний ICANN-реєстратор. Реєстрація підпадає під угоди Namecheap і наші умови.</p>'
                        . '<p>Юридичний центр Namecheap: <a href="' . $ncLegal . '" rel="noopener noreferrer">' . $ncLegal . '</a>.</p>',
                ],
                [
                    'title' => '2. Угоди, які ви приймаєте при замовленні домену',
                    'body' => '<p>Купуючи або подовжуючи домен у нас, ви підтверджуєте (pass-through):</p><ul>'
                        . '<li><a href="' . $ncReg . '" rel="noopener noreferrer">Domain Registration Agreement</a> Namecheap;</li>'
                        . '<li><a href="' . $ncTos . '" rel="noopener noreferrer">Universal Terms of Service</a>;</li>'
                        . '<li><a href="' . $ncPriv . '" rel="noopener noreferrer">Privacy Policy</a> Namecheap;</li>'
                        . '<li>за WHOIS privacy: <a href="' . $ncWhois . '" rel="noopener noreferrer">WHOIS Privacy Service Agreement</a>;</li>'
                        . '<li>політики ICANN і правила TLD/registry (вкл. .no / Norid).</li></ul>'
                        . '<p>Щодо самої реєстрації переважають правила Namecheap/registry. Наші Умови регулюють оплату, підтримку та хостинг.</p>',
                ],
                [
                    'title' => '3. Що закон і ICANN вимагають від реєстранта (вас)',
                    'body' => '<p>Ви зобов’язані зокрема:</p><ul>'
                        . '<li>надавати <strong>точні дані реєстрації</strong> і оновлювати їх (ICANN Registration Data Policy / RDDS);</li>'
                        . '<li><strong>підтверджувати email</strong> від реєстратора (інакше можлива блокування домену);</li>'
                        . '<li>не використовувати домен для <strong>незаконної діяльності</strong>;</li>'
                        . '<li><strong>вчасно оплачувати подовження</strong>;</li>'
                        . '<li>поважати права третіх осіб (торговельні марки; UDRP/суд);</li>'
                        . '<li>для національних зон (напр. <strong>.no</strong>) — локальні правила (Norid).</li></ul>'
                        . '<p>ICANN: <a href="' . $icannRights . '" rel="noopener noreferrer">права реєстранта</a> · <a href="' . $rdp . '" rel="noopener noreferrer">Registration Data Policy</a>.</p>',
                ],
                [
                    'title' => '4. Персональні дані та Namecheap (GDPR)',
                    'body' => '<p>Для реєстрації ми збираємо та передаємо реєстратору обов’язкові дані (реєстрант/контакти). Підстава: зазвичай <strong>виконання договору</strong> (ст. 6(1)(b) GDPR). Namecheap може обробляти дані в США; передача — глава V GDPR (напр. SCC). Див. Privacy Policy Namecheap і нашу <a href="' . $c['privacy'] . '">Політику конфіденційності</a>.</p>',
                ],
                [
                    'title' => '5. WHOIS privacy (якщо доступна)',
                    'body' => '<p>Privacy/proxy не скасовує обов’язок надати правдиві дані реєстратору і не захищає від судових вимог чи UDRP. Див. <a href="' . $ncWhois . '" rel="noopener noreferrer">WHOIS Privacy Service Agreement</a>.</p>',
                ],
                [
                    'title' => '6. Ціна, подовження, закінчення, повернення',
                    'body' => '<p>Ціна на checkout включає вартість реєстратора та нашу маржу. Ціна подовження може відрізнятися. Автоподовження потребує дійсного платежу та email. Після expiry — grace/redemption з можливими додатковими зборами, потім видалення.</p>'
                        . '<p><strong>Повернення:</strong> після успішної реєстрації/подовження в registry кошти зазвичай <strong>не повертаються</strong>. Споживачі в ЄЕЗ/ЄС можуть мати 14 днів на відмову, якщо не застосовано виняток для цифрових послуг — інформуємо на checkout. Пишіть на ' . $c['email'] . ' при помилковому замовленні.</p>',
                ],
                [
                    'title' => '7. Трансфер і зміна власника',
                    'body' => '<p>Трансфер між реєстраторами — за ICANN Transfer Policy (auth-код, lock, email). Ми допомагаємо, але не можемо примусити transfer при блокуванні реєстратором/registry.</p>',
                ],
                [
                    'title' => '8. DNS і хостинг',
                    'body' => '<p>Домен не включає автоматично сайт. Хостинг — окрема послуга. DNS можна спрямувати на наші або зовнішні NS. Помилки DNS — ваша відповідальність, крім документованої помилки з нашого боку.</p>',
                ],
                [
                    'title' => '9. Зловживання та блокування',
                    'body' => '<p>Namecheap і ' . $c['brand'] . ' можуть блокувати домени за phishing, malware, spam, незаконний контент або за приписом. Термінове блокування — без попередження при серйозному ризику.</p>',
                ],
                [
                    'title' => '10. Спори',
                    'body' => '<p>Спори щодо торговельних марок — <strong>UDRP</strong> або суд. Комерційні спори щодо оплати/хостингу — норвезьке право (див. Умови), без обмеження обов’язкових прав споживача в ЄЕЗ/ЄС.</p>',
                ],
                [
                    'title' => '11. Офіційні документи Namecheap',
                    'body' => '<ul>'
                        . '<li><a href="' . $ncLegal . '" rel="noopener noreferrer">Legal hub</a></li>'
                        . '<li><a href="' . $ncReg . '" rel="noopener noreferrer">Registration Agreement</a></li>'
                        . '<li><a href="' . $ncTos . '" rel="noopener noreferrer">Universal TOS</a></li>'
                        . '<li><a href="' . $ncPriv . '" rel="noopener noreferrer">Privacy Policy</a></li>'
                        . '<li><a href="' . $ncWhois . '" rel="noopener noreferrer">WHOIS Privacy</a></li></ul>',
                ],
                [
                    'title' => '12. Контакт',
                    'body' => '<p>Замовлення доменів у нас: <a href="mailto:' . $c['email'] . '">' . $c['email'] . '</a>. Питання рівня реєстратора можуть потребувати Namecheap support.</p>',
                ],
            ],
            'contact_title' => 'Питання щодо реєстрації доменів?',
            'contact_text' => 'Пишіть у support щодо замовлень через панель. Політики Namecheap — за офіційними посиланнями вище.',
        ],
    ];

    return $all[$lang] ?? $all['en'];
}

/** @return list<array<string, string>> */
function hs_legal_cookie_rows(string $lang): array
{
    $labels = [
        'no' => ['Necessary' => 'Nødvendig', 'Functional' => 'Funksjonell', 'Session' => 'Sesjon', '12 months' => '12 mnd', 'Browser session' => 'Sesjon'],
        'en' => ['Necessary' => 'Essential', 'Functional' => 'Functional', 'Session' => 'Session', '12 months' => '12 months', 'Browser session' => 'Browser session'],
        'uk' => ['Necessary' => 'Необхідний', 'Functional' => 'Функціональний', 'Session' => 'Сесія', '12 months' => '12 міс.', 'Browser session' => 'Сесія браузера'],
    ];
    $L = $labels[$lang] ?? $labels['en'];
    $t = static fn(string $k) => $L[$k] ?? $k;
    return [
        ['name' => 'PHPSESSID / hs session', 'type' => $t('Necessary'), 'purpose' => $lang === 'no' ? 'Innlogging og sikkerhet' : ($lang === 'uk' ? 'Вхід і безпека' : 'Login and security'), 'duration' => $t('Browser session')],
        ['name' => 'csrf / hs_csrf', 'type' => $t('Necessary'), 'purpose' => $lang === 'no' ? 'CSRF-beskyttelse' : ($lang === 'uk' ? 'Захист CSRF' : 'CSRF protection'), 'duration' => $t('Browser session')],
        ['name' => 'hs_lang', 'type' => $t('Necessary'), 'purpose' => $lang === 'no' ? 'Språkvalg' : ($lang === 'uk' ? 'Вибір мови' : 'Language preference'), 'duration' => $t('12 months')],
        ['name' => 'hs_cookie_consent', 'type' => $t('Necessary'), 'purpose' => $lang === 'no' ? 'Lagrer cookie-samtykke' : ($lang === 'uk' ? 'Зберігає згоду на cookie' : 'Stores cookie consent'), 'duration' => $t('12 months')],
        ['name' => 'hs_landing_* / panel UI', 'type' => $t('Functional'), 'purpose' => $lang === 'no' ? 'UI-preferanser i panel/bygger' : ($lang === 'uk' ? 'Налаштування UI в панелі' : 'Panel/builder UI preferences'), 'duration' => $t('12 months')],
    ];
}
