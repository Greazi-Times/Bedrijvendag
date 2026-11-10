# Processor and service inventory (internal)

Status is what could be verified from the repository. "Unknown" means a decision or contract is missing here; nothing is assumed.

| Service | Evidence in project | Data | Role (to confirm) | DPA | Location / transfer |
|---|---|---|---|---|---|
| Hosting (OVHcloud mentioned by the organisation) | none in code | all application data | processor | unknown | unknown |
| E-mail delivery | `MAIL_MAILER` is configured per environment; the example uses `log`. No provider named in code | recipient addresses, mail content | processor | unknown | unknown |
| DeepL | `DEEPL_API_KEY/URL` in env; `DeepLTranslator`, translation jobs for company descriptions and policy texts | company descriptions, policy text | processor (check plan: free vs. pro retention terms) | unknown | DeepL SE, Germany (**to verify**) |
| Bunny Fonts (fonts.bunny.net) | `resources/views/app.blade.php` | visitor IP and browser data | independent provider / processor, to assess | none needed if no personal data is processed beyond IP; **assess** | EU-based per provider (**verify**) |
| Cloudflare | not found in code | unknown | unknown | unknown | unknown |
| Google Photos album link | event field "Google Photos URL" in admin | none sent by us; visitors go to Google | independent controller | n/a | Google (outside EU possible) |
| Analytics / ads / embeds | none found | n/a | n/a | n/a | n/a |

Actions: collect DPAs (hosting, mail, DeepL), record processing locations, assess Bunny Fonts or self-host the fonts (removes the IP transfer), confirm whether Cloudflare sits in front of the site.
