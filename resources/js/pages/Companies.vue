<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppHeader from '@/components/AppHeader.vue';
import AppFooter from '@/components/AppFooter.vue';
import { Company } from '@/types';

const companies: Company[] = [
    {
        name: "CSi Palletising",
        description: [
            "As a world market leader and expert in automated handling systems for the Fast Moving Consumer Goods industry worldwide, CSi palletising is the specialist in fully automated handling of packaged goods and offers innovative and reliable palletising solutions for a wide range of customers.",
            "Through smart data analysis and intensive cooperation between customer and our product specialists, we jointly create a solution that is optimally tailored to the unique requirements of our customers.",
            "We have a wide range of material handling and palletising technology, from single robot palletising cells, to our fastest and most sophisticated multi load & line palletising machines.",
            "CSi palletising offers:",
            "- Palletising technology designed to fit purpose;",
            "- Case transport lines;",
            "- Pallet handling systems;",
            "- Industry 4.0 which provides all required data in order to increase productivity;",
            "- Lifecycle services and a 24/7 helpdesk for technical support.",
            "Our employees are team-oriented, results-driven professionals with a passion for our systems.",
            "Every employee contributes to the success of CSi and is appreciated for their dedication and hard work.",
            "We operate with a flat organizational structure, and our informal culture creates a pleasant and open  environment.",
        ],
        educations: [
            "Technische Informatica",
            "Industrial Engineering & Management",
            "Werktuigbouwkunde",
            "Mechatronica",
            "Elektrotechniek",
            "Technische Bedrijfskunde",
        ],
        logo: "/images/companies/svmotus.png",
        href: "https://www.csiportal.com",
        visible: true
    },
    {
        name: "TES  Industrial Systems BV",
        description: [
            "TES Industrial Systems staat voor duurzame oplossingen van industriële aandrijfconcepten en industriële automatisering.",
            "We ontwikkelen en produceren  aandrijfconcepten en automatiseringsoplossingen, die internationaal worden toegepast in onder andere containerkranen, industriële kranen, transportbandsystemen, maritieme installaties en attractieparken.",
            "TES Industrial Systems.",
            "Dat werkt.",
        ],
        educations: [
            "Mechatronica",
            "Elektrotechniek",
        ],
        logo: "/images/companies/silicium.png",
        href: "https://www.tesindus.nl",
        visible: true
    },
    {
        name: "De Groot Bewerkingsmachines",
        description: [
            "De Groot Bewerkingsmachines is expert op het gebied van bewerkingsmachines voor massief hout, plaatmateriaal, kunststoffen, composieten en schuimen.",
            "Je kunt bij ons terecht voor Europa's beste merken bewerkingsmachines, bijbehorende onderdelen en servicediensten.",
            "We streven naar een duurzame samenwerking en denken daarom graag mee hoe we het maximale uit een bewerkingsproces kunnen halen.",
            "Samen sterk in techniek en teamgevoel!",
            "Bij ons draait het om mensen en techniek.",
            "We zijn een familiebedrijf met 110 betrokken medewerkers, een showroom vol techniek en een sfeer waarin jij je direct thuis voelt.",
            "- Werk met trots aan techniek die klanten echt verder helpt",
            "- Word onderdeel van een team dat samen werkt en samen lacht",
            "- Krijg de ruimte om te leren, proberen en verbeteren",
        ],
        educations: [
            "Technische Informatica",
            "Business IT & Management",
            "Industrial Engineering & Management",
            "Werktuigbouwkunde",
            "Mechatronica",
            "Elektrotechniek",
            "Technische Bedrijfskunde",
        ],
        logo: "/images/companies/svwim.png",
        href: "https://www.degroot.nl/",
        visible: true
    },
    {
        name: "Hydrauvision",
        description: [
            "Hydrauvision is een toonaangevend internationaal bedrijf op het gebied van aandrijftechniek, gespecialiseerd in hydraulische en elektrische oplossingen.",
            "Vanuit onze vestigingen in Nederland en België werken we aan innovatieve projecten in sectoren zoals offshore, maritiem, industrie en machinebouw.",
            "Met onze kennis en ervaring leveren we complete systemen: van ontwerp en engineering tot realisatie, service en verhuur.",
            "Dit doen we niet alleen regionaal, maar wereldwijd, onze installaties en oplossingen vinden hun weg naar klanten over de hele wereld.",
            "Daarnaast zijn we een organisatie waar ontwikkeling centraal staat.",
            "We hechten veel waarde aan persoonlijke en professionele groei, en bieden onze medewerkers volop ruimte om zich te ontwikkelen en hun talenten verder uit te bouwen.",
        ],
        educations: [
            "Business IT & Management",
            "Industrial Engineering & Management",
            "Werktuigbouwkunde",
            "Mechatronica",
            "Elektrotechniek",
            "Technische Bedrijfskunde",
        ],
        logo: "/images/companies/svconnect.png",
        href: "https://www.hydrauvision.com",
        visible: true
    },
    {
        name: "Mammoet",
        description: [
            "Are you looking for an internship where you help solve even the most challenging of problems, work on the strongest machines on earth and where no day is boring?",
            "At Mammoet we set the bar high in developing smarter, safer and stronger solutions for our customer’s needs.",
            "With the team’s experience, we ensure that Mammoet keeps a leading position with an unparalleled fleet of equipment.",
        ],
        educations: [
            "Informatica",
            "Technische Informatica",
            "Werktuigbouwkunde",
            "Mechatronica",
            "Elektrotechniek",
        ],
        logo: "/images/companies/pi.png",
        href: "https://www.mammoet.com/",
        visible: true
    },
    {
        name: "Fluor",
        description: [
            "Fluor Consultants BV is focusing on developing long-term relationships with customers in revamping, debottlenecking and expanding the production capacity of their existing facilities in the process Industry in the southwest Netherlands.",
            "The Bergen op Zoom, Rotterdam and Stein offices provide the full project execution services, from conceptual engineering through commissioning and construction support.",
            "We have a unique group of people who offer bright technical solutions to our clients to enable them to create a better world based on a circular economy and the energy transition.",
        ],
        educations: [
            "Industrial Engineering & Management",
            "Werktuigbouwkunde",
            "Mechatronica",
            "Elektrotechniek",
            "Technische Bedrijfskunde",
        ],
        logo: "/images/companies/avans.png",
        href: "https://www.fluor.com/",
        visible: true
    },
    {
        name: "Kojo Engineering",
        description: [
            "Kojo Engineering is een energiek en veelzijdig ingenieursbureau met een sterk team van ruim 55 vaste collega’s.",
            "Met onze afdelingen elektrotechniek & instrumentatie, werktuigbouw & piping, bouwkunde & civiele techniek en 3D scannen werken we aan uitdagende projecten in de (petro)chemie, procesindustrie, food, overheid én energiesector.",
            "Wat ons bijzonder maakt?",
            "We zijn een familiebedrijf.",
            "Dat betekent korte lijnen, vaste gezichten en een cultuur waar iedereen elkaar kent.",
            "We bouwen graag langdurige relaties op, zowel met klanten als met nieuwe talenten zoals jij.",
            "Bij Kojo Engineering krijg je de kans om mee te werken aan projecten die er écht toe doen.",
            "Of je nu stageloopt, afstudeert of aan je eerste baan begint: wij zorgen voor goede begeleiding, een leerzame werkomgeving en volop ruimte om jezelf te ontwikkelen.",
            "Wij geloven dat talent het beste tot zijn recht komt in een team waarin je je thuis voelt.",
            "Daarom combineren we professionele uitdaging met een persoonlijke aanpak.",
            "Zo zorgen we samen dat jouw ideeën impact maken, op projecten, klanten én jouw toekomst.",
        ],
        educations: [
            "Industrial Engineering & Management",
            "Werktuigbouwkunde",
            "Mechatronica",
            "Elektrotechniek",
        ],
        logo: "/images/companies/virgo.png",
        href: "https://www.kojo.nl",
        visible: true
    },
    {
        name: "B&S International B.V.",
        description: [
            "Dit is B&S",
            "B&S is een internationale distributiepartner gevestigd in o.a.",
            "Dordrecht, met een omzet van meer dan 2 miljard.",
            "Wat doen wij?",
            "Wij zorgen dat consumentengoederen over heel de wereld worden geleverd.",
            "Anyplace.",
            "Anywhere.",
            "Anytime.",
            "Dit doen wij in samenwerking met tientallen A-merken zoals CocaCola, Heineken, Mars, etc.",
            "binnen unieke markten.",
            "Denk hierbij aan cruiseschepen, airports, militaire basissen en nog veel meer!",
            "B&S heeft meerdere locaties in Nederland en levert niet alleen food.",
            "Het is zo breed dat het van parfum tot drank, gezondheidsproducten en elektronica gaat!",
            "Stage bij B^S",
            "Ieder semester hebben wij tientallen stageplekken op verschillende afdelingen.",
            "Je bent samen met ruim 20 andere stagiaires onderdeel van een gezellige community!",
            "We hebben een speciaal stageprogramma opgezet, waar we leuke en leerzame activiteiten organiseren.",
            "En vergeet natuurlijk niet te winnen tijdens tafelvoetbal!",
            "Wil je meer weten over een stage bij B&S?",
            "Check onze studentpagina hier: https://careers.bs-group-sa.com/students/",
        ],
        educations: [
            "Informatica",
            "Technische Informatica",
            "Business IT & Management",
            "Technische Bedrijfskunde",
        ],
        logo: "/images/companies/svmotus.png",
        href: "https://www.bs-group-sa.com/",
        visible: true
    },
    {
        name: "Lely",
        description: [
            "Agriculture is a dynamic sector with an essential role: to feed a growing population.",
            "By 2050, there will be nine billion mouths to feed.",
            "That means that the world needs to produce 70% more food.",
            "Increasing food production requires efficient agriculture.",
            "Quality also needs to be maintained, in a sustainable and innovative way.",
            "Respecting human and animal.",
            "In the meantime, the environment in which our customers operate is constantly changing.",
            "This means that creating a sustainable, profitable and enjoyable future is more of an ongoing process than a done deed.",
            "Every day we help our customers all over the world make choices about how they set up and run their farms.",
            "Lely supports by providing advice and innovative solutions that contribute to efficient farm management.",
            "Present and future.",
        ],
        educations: [
            "Industrial Engineering & Management",
            "Werktuigbouwkunde",
            "Mechatronica",
            "Elektrotechniek",
            "Technische Bedrijfskunde",
        ],
        logo: "/images/companies/svmotus.png",
        href: "https://www.lely.com/",
        visible: true
    },
    {
        name: "21 Groep",
        description: [
            "De kracht van de combinatie",
            "21 Groep is de technische dienstverlener in de utiliteit, woningbouw en industrie in Zuid-West Nederland, die de klant adviseert in de energietransitie en de technische installaties ontwerpt, uitvoert, beheert en in onderhoud neemt.",
            "Waarbij onze medewerkers, kwaliteit, veiligheid en duurzaamheid centraal staan.",
        ],
        educations: [
            "Elektrotechniek",
            "Technische Bedrijfskunde",
        ],
        logo: "/images/companies/svmotus.png",
        href: "https://www.21groep.nl",
        visible: true
    },
    {
        name: "Merford Holding B.V.",
        description: [
            "Welkom bij Merford, waar stilte spreekt en techniek inspireert.",
            "Merford is dé specialist in geluidsbeheersing en akoestische oplossingen voor industrie, bouw en energie.",
            "Of het nu gaat om geluidsisolerende omkastingen voor transformatoren, deuren en roosters voor stedelijke gebouwen, of maatwerkoplossingen voor complexe industriële installaties.",
            "Merford levert kwaliteit, innovatie en betrouwbaarheid.",
            "Onze producten zijn technisch doordacht, duurzaam en esthetisch verantwoord.",
            "We combineren diepgaande expertise met klantgericht denken en maatschappelijk verantwoord ondernemen.",
            "Vanuit onze vestiging in Gorinchem en internationale aanwezigheid in België en het Verenigd Koninkrijk, bouwen we aan een stillere wereld.",
            "Bij Merford geloven we dat een prettige werkomgeving begint met goed luisteren, naar onze klanten én naar elkaar.",
            "Daarom zijn we niet alleen een leverancier, maar ook een betrokken partner.",
        ],
        educations: [
            "Business IT & Management",
            "Industrial Engineering & Management",
            "Werktuigbouwkunde",
            "Technische Bedrijfskunde",
        ],
        logo: "/images/companies/svmotus.png",
        href: "https://www.merford.com/nl",
        visible: true
    },
    {
        name: "B.A.T. Machinebouw",
        description: [
            "Al meer dan 30 jaar bouwen wij bij BAT Machinebouw aan de toekomst van productie.",
            "Wij ontwikkelen en produceren innovatieve machines die je overal in de voedingsindustrie tegenkomt – van een slimme etiketteeroplossing tot complete productielijnen die de basis vormen voor efficiënte productie.",
            "Denk bijvoorbeeld aan:",
            "- Etiketteersystemen",
            "- Casepackers",
            "- Vullijnen",
            "- Ontnesters",
            "- Palletisers",
            "- Robot pick & place systemen",
            "- Speciaalmachines",
            "We ontwikkelen, bouwen en testen onze systemen volledig in eigen huis waardoor je makkelijk en snel kunt schakelen met de diverse afdelingen.",
            "Benieuwd naar de mogelijkheden?",
            "We gaan graag met je in gesprek!",
        ],
        educations: [
            "Technische Informatica",
            "Industrial Engineering & Management",
            "Werktuigbouwkunde",
            "Mechatronica",
            "Elektrotechniek",
        ],
        logo: "/images/companies/svmotus.png",
        href: "https://bat-machinebouw.nl/",
        visible: true
    },
    {
        name: "TSG Group",
        description: [
            "TSG Group is een Nederlands ingenieursbureau met een vestiging in Eindhoven en in Geleen.",
            "Het hoofdkantoor met werkplaats in Eindhoven ligt in het hart van de Brainportregio en hightech centrum van Nederland en de vestiging in Geleen is gelegen op de materialencampus Brightlands Chemelot.",
            "Samen werken we aan technische oplossingen voor zowel binnen- als buitenlandse opdrachtgevers binnen verschillende markten zoals HTSM, Energie, Life Sciences & Health, Agri & Food, Mobility, Lighting en Consumer & Sport.",
            "TSG Group is opgericht in 1991 en bestaat uit 4 business-units, waar de disciplines design, technologie en innovatie samenkomen en elkaar versterken binnen hardware èn software.",
        ],
        educations: [
            "Informatica",
            "Technische Informatica",
            "Werktuigbouwkunde",
            "Mechatronica",
        ],
        logo: "/images/companies/svmotus.png",
        href: "https://www.tsggroup.nl",
        visible: true
    },
    {
        name: "FoodeQ Engineering",
        description: [
            "Creative technology for the food processing industry",
            "Innovatieve maatwerkmachines die voldoen aan de hoogste voedselveiligheideisen.",
            "De best presterende productielijn voor de voedingsindustrie, het perfecte productieproces, dát is wat wij nastreven en waar ons hart sneller van gaat kloppen.",
        ],
        educations: [
            "Werktuigbouwkunde",
            "Mechatronica",
        ],
        logo: "/images/companies/svmotus.png",
        href: "https://foodeq.nl/",
        visible: true
    },
    {
        name: "ArcelorMittal Projects",
        description: [
            "ArcelorMittal Projects Europe is onderdeel van het internationale concern ArcelorMittal, wereldwijd een van de grootste staalproducenten, vertegenwoordigd in ruim 60 landen.",
            "ArcelorMittal Projects Europe levert complete funderingsoplossingen aan grote, internationale opdrachtgevers.",
            "We beschikken over productielocaties in Heijningen en Moerdijk.",
            "In deze productielocaties worden grote diameter stalen buizen gemaakt en worden alle voorkomende productiewerkzaamheden verricht t.b.v.",
            "de leverantie van de stalen funderingsoplossingen voor kademuren, havens, sluizen, parkeergarages, golfbrekers en het beschoeien van rivier- en kanaaloevers.",
        ],
        educations: [
            "Industrial Engineering & Management",
            "Werktuigbouwkunde",
            "Mechatronica",
            "Elektrotechniek",
            "Technische Bedrijfskunde",
        ],
        logo: "/images/companies/svmotus.png",
        href: "http://www.arcelormittal.com/",
        visible: true
    },
    {
        name: "Unica Building Services Oosterhout",
        description: [
            "Samen werken aan een duurzame toekomst",
            "Bij Unica Oosterhout geloven we in het maken van impact – voor onze klanten, onze mensen en de wereld om ons heen.",
            "Met plezier en toewijding gaan we dagelijks aan de slag voor uitdagende projecten.",
            "Want werken met een groen hart maakt niet alleen ons werk leuker, maar draagt ook bij aan een betere wereld.",
            "Onze missie is helder: samen met onze klanten en partners verduurzamen we bestaande gebouwen.",
            "Dit doen we door een slim samenspel van energiebesparing, -opwekking en -opslag.",
            "Stap voor stap helpen we eigenaren en beheerders hun panden klimaatpositief te maken.",
            "Zo creëren we samen een veilige, gezonde en groene werk- en leefomgeving.",
        ],
        educations: [
            "Werktuigbouwkunde",
            "Mechatronica",
            "Elektrotechniek",
            "Technische Bedrijfskunde",
        ],
        logo: "/images/companies/svmotus.png",
        href: "https://www.unica.nl/building-services",
        visible: true
    },
    {
        name: "Easyflex Software b.v.",
        description: [
            "Wie zijn wij?",
            "Wij maken slimme, gebruiksvriendelijke software voor de flexbranche.",
            "Met onze software verwerken wekelijks meer dan 180.000 salarissen!",
            "We zijn ambitieus, groeien snel en willen niets minder dan de beste salarisverwerker van Nederland zijn én blijven.",
            "Werken bij Easyflex betekent",
            "Met een enthousiaste groep collega’s bouwen aan een product dat altijd in ontwikkeling is.",
            "Bij Easyflex heerst een heerlijke hectiek.",
            "Je deelt kennis en wordt op een leuke manier uitgedaagd door collega’s en klanten.",
            "Bij ons krijg je alle ruimte om je talenten optimaal te benutten.",
            "En we laten je graag shinen!",
            "#OntwikkelenMetEasyflex",
            "Bij Easyflex draait ontwikkelen om meer dan software alleen.",
            "Natuurlijk werken we dagelijks aan slimme oplossingen voor de flexbranche, maar we vinden het minstens zo belangrijk dat jij als collega blijft groeien.",
            "Professioneel én persoonlijk.",
            "Check #OntwikkelenMetEasyflex op de socials om meer te zien over werken bij Easyflex.",
        ],
        educations: [
            "Informatica",
            "Technische Informatica",
            "Business IT & Management",
        ],
        logo: "/images/companies/svmotus.png",
        href: "https://Easyflex.nl",
        visible: true
    },
    {
        name: "CarCollect",
        description: [
            "Bij CarCollect werk je in een snelgroeiende tech-organisatie waar softwareontwikkeling centraal staat.",
            "Ons team bestaat uit professionals met verschillende expertises, van development en data tot sales en accountmanagement.",
            "Wat ons verbindt is de drive om samen iets te bouwen dat écht werkt.",
            "Als developer krijg je veel verantwoordelijkheid en werk je direct aan impactvolle projecten.",
            "Je gebruikt moderne tools en frameworks, schakelt snel met collega’s en hebt de ruimte om te experimenteren en te groeien in jouw vakgebied.",
            "Of je nu wilt verdiepen in backend, frontend, data of andere technologieën, wij zorgen dat je de support en kansen krijgt om verder te ontwikkelen.",
            "Bij CarCollect zijn korte lijnen, samenwerken en kennisdelen vanzelfsprekend.",
            "Loop je ergens tegenaan?",
            "Er is altijd iemand die met je meedenkt.",
            "Vanaf dag één kun je bijdragen aan echte producten, leren van ervaren developers en meebouwen aan een platform dat dagelijks door honderden gebruikers wordt gebruikt.",
        ],
        educations: [
            "Informatica",
        ],
        logo: "/images/companies/svmotus.png",
        href: "https://careers.carcollect.com/nl/",
        visible: true
    },
    {
        name: "TrueLime",
        description: [
            "Hoi, wij zijn TrueLime!",
            "We bedenken, creëren en verbeteren digitale oplossingen.",
            "En dat doen we al meer dan 25 jaar met veel plezier.",
            "Vanuit het mooie Breda werken we met zo'n 50 collega's aan digitale oplossingen waar we trots op zijn!",
        ],
        educations: [
            "Informatica",
        ],
        logo: "/images/companies/svmotus.png",
        href: "https://www.truelime.nl",
        visible: true
    },
    {
        name: "Batenburg IAS",
        description: [
            "Wij zijn een innovatieve onderneming die gespecialiseerd is in het ontwerpen en implementeren van automatiseringssystemen voor de industrie en infratechniek.",
            "Wij richten ons daarbij op de procesbesturingssystemen zoals PLC, SCADA en DCS.",
            "Ook passen wij vanwege de groeiende informatiebehoefte steeds meer MES/MOMS systemen toe.",
        ],
        educations: [
            "Technische Informatica",
            "Mechatronica",
            "Elektrotechniek",
        ],
        logo: "/images/companies/svmotus.png",
        href: "https://batenburg.nl/ias",
        visible: true
    },
    {
        name: "Agidens",
        description: [
            "Agidens - Jouw Partner voor Procesautomatisering & Validatie",
            "Als je jouw voorsprong op de concurrentie wilt behouden, moet je in beweging blijven.",
            "Altijd.",
            "Je kunt het je niet veroorloven om stil te staan, want progress doesn’t wait.",
            "Met onze diepgaande expertise op het gebied van automatisering, software en validatie houden we je bedrijf in beweging.",
            "Bij Agidens weten we dat het op de juiste manier combineren van automatisering, digitale technologieën en services met toegevoegde waarde resulteert in productieprocessen die elke dag beter worden.",
            "Processen die veiliger, efficiënter en slimmer zijn.",
            "Flexibeler en duurzamer.",
            "Want we moeten ons nu eenmaal vandaag voorbereiden op de behoeften van morgen.",
            "Because progress never stops.",
        ],
        educations: [
            "Industrial Engineering & Management",
            "Elektrotechniek",
        ],
        logo: "/images/companies/svmotus.png",
        href: "https://www.agidens.com/",
        visible: true
    },
    {
        name: "Yitch BV",
        description: [
            "Wij zijn geen software-integrator, geen consultants, geen service-partner, noch een IT-dienstverlener.",
            "We zijn het allemaal en nog veel meer!",
            "Yitch is een firma die bedrijven vanuit Bornem, Genk & Breda helpt om hun productieprocessen te optimaliseren en digitaliseren.",
            "De WHY van Yitch maakt ons uniek, namelijk door de productie en consumptie dicht bij elkaar te houden, willen we onze klanten competitief houden in hun lokale markt en daardoor jobzekerheid en levenskwaliteit garanderen voor de toekomstige generaties.",
            "Bij Yitch werk je:",
            "- Technologie- en merkonafhankelijk",
            "- Op gevarieerde projecten van analyse tot implementatie",
            "- In uiteenlopende sectoren (food, chemicals, circular economy, smart logistics, pharma ...)",
            "- bij KMO-klanten en multinationals",
            "- In een lokale of internationale context",
            "Zin om ons team te versterken?",
            "Check onze career site www.yitch-jobs.eu of stuur een mailtje naar jobs@yitch.eu.",
        ],
        educations: [
            "Informatica",
            "Technische Informatica",
            "Business IT & Management",
        ],
        logo: "/images/companies/svmotus.png",
        href: "https://www.yitch.eu",
        visible: true
    },
    {
        name: "Frans Vervaet B.V.",
        description: [
            "Frans Vervaet BV is een familiebedrijf met een rijke historie in de agrarische sector.",
            "Wat ruim 60 jaar geleden begon met vakmanschap en inventiviteit, is uitgegroeid tot een internationaal toonaangevende speler in de ontwikkeling en productie van landbouwmachines.",
            "Met passie voor techniek en innovatie streven wij ernaar om duurzame, efficiënte en gebruiksvriendelijke oplossingen te creëren die boeren en loonwerkers écht vooruithelpen.",
            "Onze drive om telkens een stap voor te blijven, maakt dat wij niet alleen bouwen aan machines, maar ook aan de toekomst van de landbouw",
        ],
        educations: [
            "Industrial Engineering & Management",
            "Werktuigbouwkunde",
            "Mechatronica",
            "Elektrotechniek",
            "Technische Bedrijfskunde",
        ],
        logo: "/images/companies/svmotus.png",
        href: "https://www.vervaet.nl/",
        visible: true
    },
    {
        name: "Persy Control Services B.V.",
        description: [
            "Als technologie- en ontwikkelpartner, adviseur, dienstverlener en systeemintegrator biedt PCS totaaloplossingen en kennis voor de utilitaire markt.",
            "Te denken valt aan gebouwautomatisering en energiesystemen.",
            "PCS is uitgegroeid tot een vooraanstaande specialist in de intelligente gebouwen en omgevingen en energiesystemen.",
            "Zie ook onze website www.pcs-gebouwautomatisering.nl voor meer informatie over onze dynamische werkomgeving.",
            "Door onze groei in zowel projecten als relaties, is PCS op zoek naar gedreven, technische (young) professionals op het gebied van gebouwenautomatisering en meet- en regeltechniek.",
        ],
        educations: [
            "Werktuigbouwkunde",
            "Mechatronica",
            "Elektrotechniek",
        ],
        logo: "/images/companies/svmotus.png",
        href: "https://www.pcs-gebouwautomatisering.nl/",
        visible: true
    },
    {
        name: "Van Meer BV",
        description: [
            "Van Meer – Groei, kansen en een informele werksfeer!",
            "Van Meer is een snelgroeiend familiebedrijf met een internationaal karakter, waar succes voortkomt uit de kracht van dynamische teams en toegewijde professionals.",
            "Sinds januari 2025 werken we vanuit ons gloednieuwe, moderne pand in Tholen, compleet met alle faciliteiten die je maar kunt wensen.",
            "Zo hebben we een eigen gym om fit te blijven, en organiseren we regelmatig borrels en leuke activiteiten om samen te ontspannen.",
            "Bij Van Meer staat niet alleen productiviteit centraal, maar ook jouw persoonlijke en professionele groei.",
            "Onze cultuur is gericht op kennisvergroting, en we bieden volop kansen om je expertise verder te ontwikkelen.",
            "Via diverse scholingsprogramma’s zorgen we ervoor dat je jezelf kunt blijven uitdagen en verbeteren.",
            "Van Meer – Jouw partner in elektrische en mechanische aandrijfsystemen.",
            "Van Meer is dé specialist in engineering, implementatie, onderhoud en beheer van elektrische en mechanische installaties.",
            "Als system integrator en serviceprovider bedienen we uiteenlopende industriesectoren en de maritieme wereld.",
            "Wat ons uniek maakt?",
            "We bundelen al deze expertise onder één dak en begeleiden installaties gedurende hun volledige levensduur.",
        ],
        educations: [
            "Informatica",
            "Technische Informatica",
            "Mechatronica",
            "Elektrotechniek",
            "Technische Bedrijfskunde",
        ],
        logo: "/images/companies/svmotus.png",
        href: "https://www.vanmeer.nl",
        visible: true
    },
    {
        name: "VerAutomation B.V.",
        description: [
            "VerAutomation is partner voor zowel nationale als internationale bedrijven op het gebied van mechanisatie en automatisering van productieprocessen en informatiesystemen.",
            "Wij zijn werkzaam in tal van branches en we werken voor aantrekkelijke opdrachtgevers zoals een snoepfabriek, attractiebouwers en een offshore bedrijf.",
            "VerAutomation houdt zich bezig met het ontwerp en de engineering van: maatwerk aandrijf- en besturingsoplossingen, paneelbouw, commissioning en service, en turn-key projecten.",
            "Inmiddels heeft VerAutomation ruim 85 medewerkers in dienst, verdeeld over de vestigingen in Middelbeers en Andelst.",
            "De sfeer op de werkvloer wordt door onze medewerkers omschreven als informeel; iedereen doet ertoe en is bereid elkaar te helpen om de organisatie- en projectdoelstellingen te realiseren en te verbeteren.",
        ],
        educations: [
            "Mechatronica",
            "Elektrotechniek",
        ],
        logo: "/images/companies/svmotus.png",
        href: "https://www.verautomation.com/",
        visible: true
    },
    {
        name: "CIMSOLUTIONS",
        description: [
            "CIMSOLUTIONS levert al sinds 1992 hoogwaardige, professionele en innovatieve ICT consultancy- en softwarediensten aan de top 500 toonaangevende Nederlandse en Internationale organisaties.",
            "Met onderstaande dienstverlening dekken we voor u alle fasen van de software levenscyclus af:",
            "- Consultancy Diensten",
            "- Projecten onder eigen regie",
            "- AI producten & diensten",
            "- Applicatiebeheer & onderhoud",
            "- Hybride Offshoring",
            "Wij passen state-of-the-art ICT concepten en technieken toe, waardoor we altijd de optimale oplossing bieden voor u.",
            "Onze uitstekende track-record en een hoge klanttevredenheid bij onze gerenommeerde klanten in combinatie met onze innovatieve expertises, diensten en producten maakt CIMSOLUTIONS uw ideale partner.",
            "Sinds de oprichting in 1992 is CIMSOLUTIONS B.V.",
            "autonoom doorgegroeid naar een klantgerichte internationale onderneming met vestigingen in Nederland en Bangladesh.",
            "Vanuit de zes Nederlandse vestigingen in Amsterdam, Best, Deventer, Groningen, Rotterdam en Vianen bedienen we onze klanten in alle regio’s van Nederland.",
            "Voor offshore dienstverlening hebben wij een eigen vestiging in Dhaka (Bangladesh).",
            "CIMSOLUTIONS werkt met ruim 600 hoogopgeleide ICT professionals en specialisten, met minimaal een HBO- of Universitair diploma.",
            "Zij adviseren en ondersteunen u op het gebied van onder andere bestuurlijke, zakelijke, administratieve technische en industriële automatisering.",
        ],
        educations: [
            "Informatica",
            "Technische Informatica",
            "Business IT & Management",
        ],
        logo: "/images/companies/svmotus.png",
        href: "https://www.cimsolutions.nl",
        visible: true
    },
    {
        name: "Kieu Engineering",
        description: [
            "Het liefst wil je steeds iets nieuws ontwerpen.",
            "Steeds nieuwe oplossingen bedenken, samen met gelijkgestemde collega's.",
            "Dat geeft jou energie.",
            "Gewoon omdat jij ontwerpen leuk vindt.",
            "Herken jij je hierin?",
            "Dan zullen de out of the box-ontwerpen van Kieu Engineering jou aanspreken.",
            "De projecten voor onze klanten hebben één ding gemeen: we starten steeds weer met een leeg vel papier waar de eerste concepten op geschetst worden.",
            "Wij hebben dus geen kant en klare opdrachten liggen.",
            "Alles wordt uitgevoerd naar vraag en wens van de klant.",
            "Uitdagingen aangaan is ervaring opdoen; kom kennis maken met Kieu!",
        ],
        educations: [
            "Werktuigbouwkunde",
            "Mechatronica",
        ],
        logo: "/images/companies/svmotus.png",
        href: "https://www.kieu.nl",
        visible: true
    },
    {
        name: "SYMACH BV",
        description: [
            "SYMACH ontwikkelt en produceert palletiseermachines en afzakmachines die optimaal aansluiten bij uw bedrijfsactiviteiten.",
            "Daarmee leggen we de basis voor een efficiënt en flexibel logistiek proces.",
            "We bouwen onze verpakkings- en palletiseermachines op basis van jarenlang ervaring en een innovatieve kijk op mogelijkheden.",
            "Dit resulteert in een modulair ontwerp op maat, hoge betrouwbaarheid en uitstekende prestaties op het gebied van snelheid en kwaliteit.",
            "De kracht van SYMACH schuilt in de jarenlange ervaring.",
            "De kennis en expertise die dit oplevert, is terug te vinden in ieder stadium van de afzakmachine en palletiseermachine; ontwerp, bouw, installatie en onderhoud.",
            "SYMACH is gevestigd in Zeeland.",
            "Het werkterrein breidt zich echter gestaag uit.",
            "Het bedrijf is internationaal in alle windstreken actief.",
            "Daarbij maakt de dagelijkse aandacht voor kwaliteit, flexibiliteit en het hoge serviceniveau duidelijk het verschil.",
            "Kenmerkend voor SYMACH is de keuze om de mens centraal te stellen bij alles wat we doen.",
        ],
        educations: [
            "Industrial Engineering & Management",
            "Werktuigbouwkunde",
            "Mechatronica",
            "Elektrotechniek",
        ],
        logo: "/images/companies/svmotus.png",
        href: "https://www.symach.nl",
        visible: true
    },
    {
        name: "Flowserve",
        description: [
            "Flowserve is een beursgenoteerde Amerikaanse onderneming en wij engineeren, ontwikkelen, fabriceren, distribueren en onderhouden mechanische pompen, pompsystemen, afsluiters en mechanische asafdichtingen.",
            "Wij zijn actief in de volgende industrien; Power supply, Water treatment, Chemical, Oil & Gas and Hightech.",
            "Dit doen wij met 16.000 collega's wereldwijd en dichterbij huis met 650 medewerkers in Etten-Leur en 50 in Roosendaal.",
            "Wij zijn een van Flowserve's grootste plants en hebben 80 engineers in dienst.",
            "Dat maakt ons een voorname technische hub voor Flowserve Europe en zelfs global.",
            "Wij bieden uitdagingen op Engineering, Projectmanagement, Supply Chain, Warehousing, Business Intelligence, Quality en Manufacturing.",
            "Wij hebben een interne bedrijfsschool waarmee we zelf personeel opleiden voor onze 30 CNC gestuurde machines in Etten-Leur.",
            "Een toonaangevend werkgever waar jij jouw carriere in een stroomversnelling kan brengen.",
        ],
        educations: [
            "Werktuigbouwkunde",
            "Technische Bedrijfskunde",
        ],
        logo: "/images/companies/svmotus.png",
        href: "https://www.flowserve.com",
        visible: true
    },
    {
        name: "van den Buijs installaties B.V.",
        description: [
            "Maak kennis met vdBuijsInstall: jouw toekomst in de installatietechniek!",
            "vdBuijsInstall is gevestigd in een modern pand aan het Rithmeesterpark in Breda.",
            "Als groeiend bedrijf beschikken we over een schat aan kennis dankzij onze ervaren collega’s.",
            "Die kennis dragen we graag over aan de volgende generatie – daarom hebben we ruimte voor enthousiaste studenten zoals jij!",
            "Wij zijn gespecialiseerd in werktuigbouwkundige installaties, waaronder (duurzame) verwarming, koeling, ventilatie, loodgieterswerk en sanitair.",
            "Ongeveer 60% van ons team werkt in de montage, terwijl de overige 40% zich bezighoudt met calculatie, engineering en werkvoorbereiding op kantoor.",
            "Samen zorgen we voor hoogwaardige eindresultaten.",
            "Bij vdBuijsInstall krijg je de kans om je te ontwikkelen tot een échte specialist in je vakgebied.",
            "Met volop doorgroeimogelijkheden en een stimulerende werkomgeving kun je hier het beste uit jezelf halen.",
            "Benieuwd naar de mogelijkheden?",
            "We ontmoeten je graag tijdens de Speed-Meets!",
        ],
        educations: [
            "Werktuigbouwkunde",
            "Mechatronica",
        ],
        logo: "/images/companies/svmotus.png",
        href: "https://www.vandenbuijs.nl/",
        visible: true
    },
    {
        name: "BOM Engineering B.V.",
        description: [
            "Bij BOM Engineering zijn we uitvinders van oplossingen op technische vraagstukken van onze klanten.",
            "Alles is maatwerk op bestelling, BOM heeft geen eigen product of seriematig/ terugkerend werk.",
            "Theorie en praktijk komen samen onder 1 dak.",
            "Per schooljaar bieden wij hebben wij plaats voor 10 tot 12 studenten.",
            "(3e jaars stage of afstuderen)",
            "Ook benieuwd naar de mogelijkheden?",
            "Schrijf je in voor een speeddate met BOM Engineering en ga direct in gesprek met 1 van de directieleden of kijk alvast binnen via de virtuele tour op onze website.",
            "Graag tot op de Bedrijvendag!",
        ],
        educations: [
            "Industrial Engineering & Management",
            "Werktuigbouwkunde",
            "Mechatronica",
        ],
        logo: "/images/companies/svmotus.png",
        href: "https://bomengineering.nl",
        visible: true
    },
    {
        name: "Lion Laser Systems B.V.",
        description: [
            "Lion Lasers is dé Nederlandse fabrikant van industriële lasersystemen om mee te graveren, snijden lassen en reinigen.",
            "Wij ontwikkelen en bouwen lasersystemen voor diverse (niche)markten.",
            "Bij Lion Lasers geloven we in de kracht van innovatie en samenwerking.",
            "Of het nu gaat om het ontwikkelen van slimme lasermachines of het oplossen van uitdagende vraagstukken, we houden ervan om net dat stapje extra te zetten.",
            "Met passie voor technologie en een flinke dosis creativiteit denken we graag met u mee.",
            "Kwaliteit, service en een open blik naar de toekomst vormen de kern van wie we zijn.",
            "Gevestigd in het mooie Breda met een team van laserfanaten proberen wij de wereld een stukje groener te maken door oplossingen te bedenken die het gebruik van chemicaliën tegengaat.",
        ],
        educations: [
            "Technische Informatica",
            "Industrial Engineering & Management",
            "Werktuigbouwkunde",
            "Mechatronica",
            "Elektrotechniek",
            "Technische Bedrijfskunde",
        ],
        logo: "/images/companies/svmotus.png",
        href: "https://www.lionlasers.nl",
        visible: true
    },
    {
        name: "HuRis BV",
        description: [
            "HuRis is een implementatiepartner van SAP SuccessFactors.",
            "Wij verkopen, implementeren en onderhouden deze software bij meer dan 120 klanten.",
            "Daarnaast hebben we eigen software die extra functionaliteit toevoegt.",
            "Wij zijn de grootste SAP SuccessFactors partner in Nederland en al 2 opvolgende jaren tot partner of the year verkozen.",
            "Ons doel?",
            "Kwalitatief hoogwaardige consultancy leveren, betrouwbaar support bieden aan onze klanten en projecten boven verwachting en binnen budget uitvoeren.",
            "Wij geloven er namelijk in dat een goede kwaliteit van dienstverlening voorwaarde is voor een motiverende en inspirerende organisatie en leidt tot lange termijn relaties met klanten, medewerkers en partners.",
            "Bij HuRis werk je als consultant met passie en plezier, met veel vrijheid, leuke collega's en goed voorwaarden.",
        ],
        educations: [
            "Informatica",
            "Technische Informatica",
            "Business IT & Management",
            "Technische Bedrijfskunde",
        ],
        logo: "/images/companies/svmotus.png",
        href: "https://huris.nl",
        visible: true
    },
    {
        name: "Viscon Group Services B.V.",
        description: [
            "We are Viscon Group.",
            "Passionate about agro and food processes and the limitless possibilities technology has to offer.",
            "Our machinery, software and integrated solutions enable the world to produce, distribute and enjoy healthy fresh food, flowers and plants.",
            "We know no bounds when it comes to adventure, diversity and inspiration.",
            "As tech-enthusiasts, we dive head-first into challenging processes, using technology to create innovative solutions.",
            "We dare to experiment.",
            "The development of ideas and talent: that’s our goal!",
        ],
        educations: [
            "Informatica",
            "Technische Informatica",
            "Werktuigbouwkunde",
            "Mechatronica",
            "Elektrotechniek",
            "Technische Bedrijfskunde",
        ],
        logo: "/images/companies/svmotus.png",
        href: "https://viscongroup.eu/",
        visible: true
    },
    {
        name: "In Summa",
        description: [
            "Werken bij In Summa is jezelf ontwikkelen, veel leren en blijven groeien.",
            "Ook In Summa blijft groeien waardoor wij altijd op zoek zijn naar specialisten & super nerds op het gebied van sales, software en techniek.",
            "Naast persoonlijke groei en resultaatgericht werken aan uitdagende opdrachten voor onze klanten, vinden wij het ook belangrijk om de In Summa “team spirit” te behouden.",
            "Borrels, uitjes, interactieve kennissessies, LAN-Party's…..",
            "“Hoe meer plezier je hebt in je werk, hoe beter je presteert” is dan ook ons motto.",
            "Technisch hoogwaardige oplossingen en uitstekende service is waar In Summa voor staat.",
            "Dit gecombineerd met een informele werksfeer en een gezonde ambitie maakt In Summa tot een uitdagende werkgever!",
            "Wil jij het maximale uit je stage halen?",
            "Geen stoffige vooraf in elkaar geknutselde saaie opdracht!",
            "Je gaat samen met één van onze InSummisten een opdracht bedenken, die voldoet aan alle afstudeereisen en die 100% bij jou past.",
            "Awesome toch?",
        ],
        educations: [
            "Informatica",
            "Business IT & Management",
            "Werktuigbouwkunde",
        ],
        logo: "/images/companies/svmotus.png",
        href: "https://www.insumma.nl",
        visible: true
    },
    {
        name: "VVA-informatisering",
        description: [
            "VVA is een innovatief en onafhankelijk informatiseringsbureau dat zich voornamelijk richt op woningcorporaties, zorginstellingen, lokale overheden en het onderwijs.",
            "Wij ondersteunen organisaties bij hun digitale transformatie door het geven van adviezen, leveren van producten en het begeleiden van projecten.",
            "Dit doen we met een team van enthousiaste professionals die expert zijn op het snijvlak van organisatie en ICT.",
            "In onze filosofie voert de klant regie en zit aan het stuur.",
            "Met onze vaardigheden, kennis en producten helpen we organisaties bewust keuzes te maken.",
            "Wij werken met plezier en passie voor het vak en stralen dat uit.",
            "Om onze klanten steeds beter van dienst te kunnen zijn en flexibel in te kunnen spelen op nieuwe innovaties, trends en ontwikkelingen groeit ook onze dienstverlening.",
        ],
        educations: [
            "Informatica",
            "Business IT & Management",
        ],
        logo: "/images/companies/svmotus.png",
        href: "https://vva-informatisering.nl/",
        visible: true
    },
    {
        name: "MA-IT My Automation",
        description: [
            "MA-IT MyAutomation is de kennis leverancier voor de industriële- en kantoorautomatisering.",
            "Vanuit vestigingen in Deventer, Eindhoven, Gorinchem, Amsterdam en Assen, zijn onze engineers dagelijks bezig om mooie software oplossingen voor gerenommeerde klanten  te ontwerpen, ontwikkelen en onderhouden.",
        ],
        educations: [
            "Technische Informatica",
            "Mechatronica",
            "Elektrotechniek",
        ],
        logo: "/images/companies/svmotus.png",
        href: "https://www.my-automation.nl",
        visible: true
    },
    {
        name: "ihomer",
        description: [
            "Wij zijn een organisatie die het volgens de buitenwereld ‘een beetje anders’ doet, maar die zelf denkt dat de rest van de wereld gek is.",
            "De menselijke maat in alles wat wij doen vinden wij belangrijk.",
            "Wij zijn ervan overtuigd dat als je goede mensen bij elkaar zet, er mooie dingen gaan ontstaan.",
            "Wij zien het als onze roeping om een ‘vrijplaats’ te creëren waar vakmensen met elkaar hun vak kunnen uitoefenen.",
            "Een plaats waar samenwerken vanzelf gaat, waar enthousiasme en plezier de regel zijn, en dit uitstraalt naar de omgeving.",
            "Wij willen het voorbeeld zijn dat software ontwikkeling leuk, inspirerend en motiverend werkt.",
            "Die vrijplaats is ihomer geworden, gebouwd op onze kernwaardes: willen, samen en simpel.",
        ],
        educations: [
            "Informatica",
            "Technische Informatica",
        ],
        logo: "/images/companies/svmotus.png",
        href: "https://www.ihomer.nl/en",
        visible: true
    },
]


import { ref, computed } from 'vue';


const selectedCompany = ref<Company | null>(null);
const visibleCompanies = computed(() => companies.filter(c => c.visible));

// Pagination
const currentPage = ref(1);
const itemsPerPage = 10;
const totalPages = computed(() => Math.ceil(visibleCompanies.value.length / itemsPerPage));
const paginatedCompanies = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  return visibleCompanies.value.slice(start, start + itemsPerPage);
});

</script>

<template>
    <Head title="Companies" />

    <AppHeader class="sticky top-0 z-50 bg-primary/8 backdrop-blur supports-[backdrop-filter]:bg-primary/8 dark:bg-[#161615]/80" />
    <header class="flex min-h-[30vh] flex-col items-center justify-center p-6 text-foreground">
        <h1 class="text-3xl md:text-6xl font-bold">Deelnemende Bedrijven</h1>
        <span class="mt-2 block h-1 w-1/2 lg:w-1/4 bg-primary"></span>
        <p class="mb-8 max-w-2xl pt-10 text-center text-gray-600">Bekijk alle bedrijven die aanwezig zullen zijn bij de komende editie op woensdag 19 november 2025.</p>
    </header>


    <!-- Companies -->
    <section class="pb-20">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-20 p-6 w-3/4 mx-auto">
          <div v-for="company in paginatedCompanies" :key="company.name" class="flex items-center justify-center">
            <div class="bg-white rounded-lg shadow hover:shadow-lg transition overflow-hidden w-full">
              <div class="flex items-center justify-center p-6 bg-blue-500/20">
                <img :src="company.logo" :alt="company.name" class="h-24 object-contain" />
              </div>
              <div class="p-4 border-t">
                <h3 class="font-semibold text-lg mb-2">{{ company.name }}</h3>
                <p class="text-sm text-gray-600 line-clamp-3">{{ company.description[0] }}</p>
                <div class="flex justify-end mt-4">
                  <button @click="selectedCompany = company" class="bg-primary text-white px-4 py-2 rounded hover:bg-primary/90">
                    Meer Lezen
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="flex justify-center items-center gap-4 mt-6">
          <button @click="currentPage = Math.max(currentPage - 1, 1)" :disabled="currentPage === 1" class="px-4 py-2 bg-gray-200 rounded disabled:opacity-50">Vorige</button>
          <span>Pagina {{ currentPage }} van {{ totalPages }}</span>
          <button @click="currentPage = Math.min(currentPage + 1, totalPages)" :disabled="currentPage === totalPages" class="px-4 py-2 bg-gray-200 rounded disabled:opacity-50">Volgende</button>
        </div>

        <!-- Modal -->
        <div v-if="selectedCompany" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
          <div class="bg-white dark:bg-gray-900 text-foreground max-w-3xl w-full rounded-lg shadow-lg overflow-y-auto max-h-[90vh] p-6 relative">
            <button @click="selectedCompany = null" class="absolute top-4 right-4 text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">
              ✕
            </button>
            <div class="flex flex-col items-center">
              <img :src="selectedCompany.logo" :alt="selectedCompany.name" class="h-24 object-contain mb-4" />
              <h2 class="text-2xl font-bold mb-4">{{ selectedCompany.name }}</h2>
            </div>
            <div class="space-y-3">
              <p v-for="(desc, i) in selectedCompany.description" :key="i" class="text-gray-700 dark:text-gray-300">
                {{ desc }}
              </p>
            </div>
            <div class="mt-6">
              <h3 class="font-semibold">Opleidingen:</h3>
              <ul class="list-disc list-inside">
                <li v-for="(edu, i) in selectedCompany.educations" :key="i">{{ edu }}</li>
              </ul>
            </div>
            <div class="mt-6">
              <a :href="selectedCompany.href" target="_blank" class="inline-block bg-primary text-white px-4 py-2 rounded hover:bg-primary/90">
                Bekijk Website
              </a>
            </div>
          </div>
        </div>
    </section>

    <AppFooter />

</template>
