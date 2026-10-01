# Credits and licences

Every external asset in this theme, where it came from, and the terms it is used under.
The brand, copy and contact details are placeholders written for this project; nothing is
taken from the audited reference site (driveoexclusive.com).

## Fonts — `assets/fonts/`

Self-hosted WOFF2 files, Latin subset, downloaded from Google Fonts on 2026-09-30.
All three families are licensed under the **SIL Open Font License 1.1**, which permits
self-hosting, embedding and commercial use. <https://openfontlicense.org>

| File | Family | Designer | Source version |
|---|---|---|---|
| `fraunces.woff2` | Fraunces (variable: opsz, wght 400–600) | Undercase Type (Phaedra Charles, Flavia Zimbardi) | Google Fonts v38 |
| `fraunces-italic.woff2` | Fraunces Italic (variable: opsz, wght 400–500) | Undercase Type | Google Fonts v38 |
| `manrope.woff2` | Manrope (variable: wght 400–700) | Mikhail Sharanda | Google Fonts v20 |
| `space-mono-400.woff2` | Space Mono Regular | Colophon Foundry | Google Fonts v17 |
| `space-mono-700.woff2` | Space Mono Bold | Colophon Foundry | Google Fonts v17 |

## Icons — `inc/icons.php`

Path data from **Tabler Icons v3.48.0** (outline set) by Paweł Kuna, **MIT License**.
<https://tabler.io/icons> · <https://github.com/tabler/tabler-icons/blob/main/LICENSE>

Used: brand-whatsapp, brand-linkedin, brand-instagram, brand-facebook, brand-tiktok,
chevron-down, plus, minus, player-pause, player-play, arrow-down-right, arrow-up-right,
check, phone, mail.

## Photography and video — `tools/media/` (imported into the Media Library)

All from Pexels under the **Pexels License**: free for commercial and non-commercial use,
no attribution required, modification allowed. Do not sell unaltered copies, and do not
imply endorsement by people or brands shown. <https://www.pexels.com/license/>

| File | Original | Author | Changes |
|---|---|---|---|
| `hero-harbour-1080.mp4` | "Black Car at Night" — <https://www.pexels.com/video/black-car-at-night-13643106/> | Erik Mclean | 1920×1080 H.264 rendition, unaltered (desktop) |
| `hero-harbour-720.mp4` | Same video | Erik Mclean | 1280×720 H.264 rendition, unaltered (phones ≤767px) |
| `hero-poster.jpg` | Frame at 0.2 s of the video above | Erik Mclean | Frame export, JPEG q82 |
| `fleet-business.webp` | "A Black Car Parked on the Street at Night" — <https://www.pexels.com/photo/a-black-car-parked-on-the-street-at-night-5707124/> | Deane Bayas | 4:3 crop, 1600×1200 WebP q78 |
| `fleet-first.webp` | "Luxurious Black Car at Dusk" — <https://www.pexels.com/photo/luxurious-black-car-at-dusk-15071553/> | Bayram Yalçın | 4:3 crop from portrait original, 1600×1200 WebP q78 |
| `fleet-van.webp` | "Black Mercedes V-Class parked by the Sidewalk with the Side Door Open" — <https://www.pexels.com/photo/black-mercedes-v-class-parked-by-the-sidewalk-with-the-side-door-open-17455633/> | Yusuf Çelik | 4:3 crop, 1600×1200 WebP q78 |

A shared colour grade is applied in CSS (`.dx-fleet-media img`), not baked into the files.

Vehicles show manufacturer badges. The Pexels License does not grant trademark rights:
do not present the site as endorsed by, or affiliated with, any vehicle manufacturer.

## Placeholder content

| Item | Value | Why it is safe |
|---|---|---|
| Brand | "Atelier Chauffeurs", monogram "A" | Invented placeholder; replace before launch |
| Logo | `tools/media/logo-placeholder.png` | Original: the "A" set in Fraunces (OFL) on a dark tile, rendered for this project. Replace the image in Elementor (header, hero and footer Image widgets) |
| Phone | +44 20 7946 0000 | Ofcom range reserved for drama/fiction |
| WhatsApp | +44 7700 900000 | Ofcom range reserved for drama/fiction |
| Email | hello@example.com | IANA-reserved example domain |
| Testimonial | Marked "Placeholder" on the page | Must be replaced with a real, attributable client quote |
| Social links | Network home pages | Replace with the real profile URLs in the Shortcode widget |
