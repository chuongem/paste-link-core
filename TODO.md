# PasteLink Core Backend TODO

File nay dung de theo doi toan bo feature se lam cho PasteLink Core backend.

Stack muc tieu:

- Backend: Laravel
- API Auth: Laravel Sanctum
- Database: MySQL
- Runtime: Docker / Docker Compose
- API Docs: Swagger via `openapi.yml`
- Queue / Jobs: Redis + Horizon
- Storage: Local disk cho dev, S3-compatible storage cho production

Trang thai:

- [ ] Chua lam
- [x] Da hoan thanh
- [~] Dang lam / can tach nho them
- [!] Bi chan / can quyet dinh them

---

## 0. Project Foundation

- [x] Khoi tao Laravel project cho backend API
- [x] Cau hinh `.env.example`
- [x] Cau hinh Dockerfile cho Laravel app
- [x] Cau hinh `docker-compose.yml`
- [x] Tao service MySQL trong Docker
- [x] Tao service Redis trong Docker
- [x] Tao service queue worker
- [!] Tao service Horizon dashboard
- [ ] Can nhac them Laravel Horizon khi queue jobs tang nhieu
  - Note: Dang bi chan vi `laravel/horizon` chua cai duoc tren Windows CLI thieu `pcntl`/`posix`; can cai trong Docker/Linux hoac cho ban Horizon tuong thich Laravel 13.
- [x] Cau hinh storage local cho moi truong dev
- [x] Cau hinh S3-compatible storage qua env
- [x] Thiet lap coding standard / formatter
- [x] Thiet lap PHPUnit / Pest test runner
- [x] Thiet lap health check API
- [x] Tao file `openapi.yml`
- [x] Cau hinh Swagger UI doc endpoint
- [x] Them convention response JSON chung
- [x] Them convention error response chung
- [x] Them request validation pattern
- [x] Them API version prefix, vi du `/api/v1`

---

## 1. Authentication and Users

- [x] Tao migration `users`
- [x] Tao model `User`
- [x] Dang ky tai khoan
- [x] Dang ky / dang nhap bang Google OAuth
- [x] Gui email thong bao dang ky thanh cong
- [x] Tao mailable `RegisteredSuccessfully`
- [x] Tao email template welcome sau dang ky
- [x] Tao mail service `MailtrapSender` cho registration email va test email
- [x] Them cau hinh Mailtrap SMTP mau vao `.env.example`
- [x] Dang nhap
- [x] Dang xuat
- [x] Lay thong tin user hien tai
- [ ] Refresh / rotate API token neu can
- [x] Bao ve API bang Laravel Sanctum
- [ ] Gioi han upload / AI feature theo user
- [ ] Them role field neu can cho admin sau nay
- [x] Viet OpenAPI schema cho auth APIs
- [x] Viet test cho auth APIs va registration email

API du kien:

- [x] `POST /api/v1/auth/register`
- [x] `POST /api/v1/auth/login`
- [x] `GET /api/v1/auth/google/redirect`
- [x] `GET /api/v1/auth/google/callback`
- [x] `POST /api/v1/auth/logout`
- [x] `GET /api/v1/me`

---

## 2. File Upload Core

- [x] Tao migration `files`
- [x] Tao model `File`
- [x] Tao file upload service
- [x] Ho tro upload single file qua multi payload
- [x] Ho tro upload multiple files
- [x] Zip multiple files trong mot upload session thanh mot file `.zip` co link tai truc tiep
- [ ] Ho tro file tu file picker
- [ ] Ho tro file tu drag and drop o API level
- [ ] Ho tro file tu clipboard paste o API level
- [x] Validate file size theo upload session toi da 300MB
- [ ] Validate MIME type / extension
- [x] Luu metadata file goc
- [x] Luu ten file goc
- [ ] Luu ten file hien thi sau khi rut gon
- [x] Luu kich thuoc file
- [x] Luu MIME type
- [ ] Luu extension
- [x] Luu storage disk
- [x] Luu storage path
- [x] Luu owner user id
- [x] Tao date-based storage path `uploads/YYYYMMDD-filename`
- [ ] Rut gon filename dai hon nguong cau hinh
- [x] Tao service xoa file vat ly khi record bi xoa
- [x] Tao endpoint danh sach file cua user
- [x] Tao endpoint chi tiet file
- [x] Tao endpoint xoa file
- [x] Viet OpenAPI schema cho file APIs
- [x] Viet test cho upload APIs

API du kien:

- [x] `POST /api/v1/files`
- [x] `GET /api/v1/files`
- [x] `GET /api/v1/files/{id}`
- [x] `DELETE /api/v1/files/{id}`

---

## 3. Public Share Links

- [x] Tao migration `share_links`
- [x] Tao model `ShareLink`
- [x] Sinh public code ngan, vi du `A7X29K`
- [x] Dam bao public code la duy nhat
- [x] Tao public URL `/f/{code}`
- [x] Gan moi file voi mot share link mac dinh khi user goi endpoint tao link
- [x] Tao endpoint lay thong tin public file theo code
- [x] Tao endpoint tai file public
- [x] Tao endpoint copy/open link data cho frontend
- [x] Tao endpoint QR code data hoac QR image
- [x] Them status active/inactive cho share link
- [x] Them soft delete neu can
- [x] Viet OpenAPI schema cho share link APIs
- [x] Viet test cho share link APIs

API du kien:

- [x] `POST /api/v1/files/{id}/share-links`
- [x] `GET /api/v1/share-links/{code}`
- [x] `GET /api/v1/f/{code}`
- [x] `GET /api/v1/f/{code}/download`
- [x] `GET /api/v1/f/{code}/qr-code`

---

## 4. GetLink File Delivery Scope

- [ ] Giu upload-getlink tap trung vao upload, share link, QR, download
- [ ] Khong xu ly preview/audio/video analysis trong luong upload-getlink
- [ ] Neu can xem file tren frontend, dung browser native hoac signed download URL don gian
- [ ] Cho phep import file da upload sang AI workspace neu user muon phan tich

API du kien:

- [ ] `POST /api/v1/files/{id}/send-to-ai`

---

## 5. Subscription Plans

- [ ] Tao migration `plans`
- [ ] Tao model `Plan`
- [ ] Seed Free plan
- [ ] Seed Daily plan
- [ ] Seed Weekly plan
- [ ] Seed Monthly plan
- [ ] Tao migration `user_subscriptions`
- [ ] Tao model `UserSubscription`
- [ ] Gan user moi vao Free plan
- [ ] Theo doi ngay bat dau subscription
- [ ] Theo doi ngay het han subscription
- [ ] Theo doi trang thai subscription
- [ ] Enforce Free plan: 10 uploads total
- [ ] Enforce paid plan: 30 uploads per day
- [ ] Tao endpoint danh sach plans
- [ ] Tao endpoint subscription hien tai
- [ ] Viet OpenAPI schema cho plan APIs
- [ ] Viet test cho upload limits

Plans:

- [ ] Free: 10 uploads total
- [ ] Daily: $2/day, 30 uploads per day
- [ ] Weekly: $8/week, 30 uploads per day
- [ ] Monthly: $20/month, 30 uploads per day

API du kien:

- [ ] `GET /api/v1/plans`
- [ ] `GET /api/v1/subscription`
- [ ] `POST /api/v1/subscription/checkout`

---

## 6. Wallet and Credit System

- [ ] Tao migration `wallets`
- [ ] Tao model `Wallet`
- [ ] Tao wallet mac dinh cho user moi
- [ ] Tao migration `credit_transactions`
- [ ] Tao model `CreditTransaction`
- [ ] Luu balance hien tai
- [ ] Luu transaction top-up
- [ ] Luu transaction spend
- [ ] Luu transaction refund neu AI job fail
- [ ] Dinh nghia credit packages
- [ ] Dinh nghia credit costs cho AI features
- [ ] Chi tru credit sau khi xu ly thanh cong
- [ ] Khoa tranh race condition khi tru credit
- [ ] Tao endpoint lay wallet balance
- [ ] Tao endpoint lich su giao dich credit
- [ ] Viet OpenAPI schema cho wallet APIs
- [ ] Viet test cho wallet va credit deduction

Top-up packages:

- [ ] $5 -> 500 credits
- [ ] $10 -> 1,100 credits
- [ ] $20 -> 2,400 credits
- [ ] $50 -> 6,500 credits

Credit usage:

- [ ] AI Transcript -> 300 credits
- [ ] AI Audio Analysis -> 300 credits
- [ ] AI Video Analysis -> 400 credits
- [ ] AI Summary -> 100 credits
- [ ] AI Translation -> 100 credits
- [ ] AI Chat with File -> 50 credits
- [ ] AI Data Extraction -> 150 credits

API du kien:

- [ ] `GET /api/v1/wallet`
- [ ] `GET /api/v1/wallet/transactions`
- [ ] `POST /api/v1/wallet/top-up/checkout`

---

## 7. Payment Integration

- [ ] Cau hinh Stripe keys qua env
- [ ] Tao checkout cho subscription
- [ ] Tao checkout cho credit top-up
- [ ] Tao webhook endpoint cho Stripe
- [ ] Verify Stripe webhook signature
- [ ] Cap nhat subscription sau payment success
- [ ] Cong credit sau payment success
- [ ] Luu payment records
- [ ] Xu ly payment failed / expired
- [ ] Viet OpenAPI schema cho payment APIs
- [ ] Viet test webhook voi fake payload

API du kien:

- [ ] `POST /api/v1/payments/stripe/webhook`

---

## 8. AI Foundation

### AI Upload Workspace

- [x] Tao migration `ai_uploads`
- [x] Tao model `AiUpload`
- [x] Upload file rieng cho tab AI, khong tu dong tao public share link
- [x] Ho tro import file tu `files` neu user bam "Send to AI"
- [x] Luu `user_id`
- [x] Luu `source_file_id` nullable khi import tu upload-getlink
- [x] Luu `source_type`: direct_upload, imported_file
- [x] Luu `original_name`
- [x] Luu `display_name`
- [x] Luu `mime_type`
- [x] Luu `extension`
- [x] Luu `size_bytes`
- [x] Luu `storage_disk`
- [x] Luu `storage_path`
- [x] Luu `checksum`
- [x] Luu `file_kind`: text, document, audio, video, image, archive, unknown
- [x] Luu `status`: uploaded, queued, analyzing, completed, failed
- [x] Luu `detected_language` nullable
- [x] Luu `metadata` JSON cho duration, dimensions, page_count, encoding, word_count
- [x] Tao AI upload service dung storage path rieng `ai-uploads/YYYYMMDD/...`
- [x] Validate kich thuoc AI upload theo plan/credit
- [ ] Validate MIME/extension theo danh sach AI ho tro
- [x] Tao endpoint upload file vao AI workspace
- [x] Tao endpoint danh sach AI uploads cua user
- [x] Tao endpoint chi tiet AI upload
- [x] Tao endpoint xoa AI upload va output lien quan

### AI Job Infrastructure

- [x] Tao migration `ai_jobs`
- [x] Tao model `AiJob`
- [ ] Tao queue job base cho AI processing
- [ ] Luu trang thai AI job: pending, processing, completed, failed
- [x] Luu `ai_upload_id`
- [x] Luu `job_type`: analyze, transcribe, summarize, translate, extract, chat_index
- [x] Luu `provider`: openai
- [x] Luu `model`
- [x] Luu `input_options` JSON: language, target_language, extraction_type, prompt
- [x] Luu output JSON/text
- [x] Luu error message khi fail
- [x] Luu credit cost
- [x] Luu `started_at`, `completed_at`
- [ ] Chi cap nhat credit khi job thanh cong
- [x] Tao endpoint xem trang thai AI job
- [x] Tao endpoint xem ket qua AI job
- [x] Tao provider abstraction cho OpenAI/Gemini/local

### AI Outputs

- [x] Tao migration `ai_outputs`
- [x] Tao model `AiOutput`
- [x] Luu `ai_upload_id`
- [x] Luu `ai_job_id`
- [x] Luu `output_type`: text_preview, transcript, summary, translation, extraction, thumbnail, subtitles, chapters
- [x] Luu `title`
- [x] Luu `content_text` nullable
- [x] Luu `content_json` nullable
- [x] Luu `storage_disk` nullable cho output file
- [x] Luu `storage_path` nullable cho thumbnail/subtitle/export
- [x] Luu `metadata` JSON
- [x] Tao endpoint list outputs theo AI upload
- [x] Tao endpoint xem mot output
- [ ] Tao endpoint download output file neu co

### AI Type Detection and Routing

- [ ] Detect text file: `txt`, `md`, `json`
- [ ] Detect document file: `pdf`, `docx`
- [ ] Detect audio file: `mp3`, `wav`, `m4a`
- [ ] Detect video file: `mp4`, `mov`, `webm`
- [ ] Map file_kind sang actions hop le
- [ ] Tu dong goi workflow mac dinh theo file_kind neu user chon "Analyze"
- [ ] Tra ve loi ro rang neu file chua ho tro

### AI Text Processing

- [ ] Tao preview noi dung text gioi han kich thuoc
- [ ] Tao endpoint view full content co gioi han an toan
- [ ] Format JSON preview neu file la JSON
- [ ] Xu ly encoding text co ban
- [ ] Tao summary tu text file
- [ ] Dich text file English -> Vietnamese
- [ ] Dich text file Vietnamese -> English

### AI Document Processing

- [ ] Extract text tu PDF neu co text layer
- [ ] Extract text tu DOCX
- [ ] Luu page_count vao metadata
- [ ] Tao summary tu document
- [ ] Tao key points tu document
- [ ] Tao action items tu document neu phu hop
- [ ] Chuan bi OCR cho PDF scan/image trong tuong lai

### AI Audio Processing

- [ ] Ho tro transcription cho MP3
- [ ] Ho tro transcription cho WAV
- [ ] Ho tro transcription cho M4A
- [ ] Luu duration vao metadata
- [ ] Goi OpenAI Speech-to-Text
- [ ] Detect English
- [ ] Detect Vietnamese
- [ ] Luu full transcript
- [ ] Tao transcript co timestamp neu provider ho tro
- [ ] Tao summary tu transcript
- [ ] Tao key points tu transcript
- [ ] Tao action items tu transcript
- [ ] Chuan bi schema cho speaker identification trong tuong lai

### AI Video Processing

- [ ] Ho tro upload MP4
- [ ] Ho tro upload MOV
- [ ] Ho tro upload WEBM
- [ ] Luu duration vao metadata
- [ ] Luu width/height vao metadata
- [ ] Extract audio track de transcription
- [ ] Tao thumbnail/preview frame luu vao `ai_outputs`
- [ ] Tao transcript tu audio track
- [ ] Tao summary tu transcript
- [ ] Tao chapters/timeline neu transcript co timestamp
- [ ] Tao action items neu noi dung la meeting/lesson

### AI Translation

- [ ] Dich transcript English -> Vietnamese
- [ ] Dich transcript Vietnamese -> English
- [ ] Dich document text English -> Vietnamese
- [ ] Dich document text Vietnamese -> English
- [ ] Dich subtitles/transcript theo timestamp neu co

API du kien:

- [ ] `POST /api/v1/ai/uploads`
- [ ] `GET /api/v1/ai/uploads`
- [ ] `GET /api/v1/ai/uploads/{id}`
- [ ] `DELETE /api/v1/ai/uploads/{id}`
- [ ] `POST /api/v1/ai/uploads/{id}/analyze`
- [ ] `POST /api/v1/ai/uploads/{id}/transcribe`
- [ ] `POST /api/v1/ai/uploads/{id}/summarize`
- [ ] `POST /api/v1/ai/uploads/{id}/translate`
- [ ] `POST /api/v1/ai/uploads/{id}/extract`
- [ ] `GET /api/v1/ai/uploads/{id}/outputs`
- [ ] `GET /api/v1/ai/outputs/{id}`
- [ ] `GET /api/v1/ai/jobs/{id}`
- [ ] `GET /api/v1/ai/jobs/{id}/result`

---

## 9. AI Workspace

### AI Chat with Files

- [ ] Tao migration `file_conversations`
- [ ] Tao model `FileConversation`
- [ ] Tao migration `file_messages`
- [ ] Tao model `FileMessage`
- [ ] Gan conversation voi `ai_upload_id`
- [ ] Tao endpoint tao conversation theo AI upload
- [ ] Tao endpoint gui cau hoi ve AI upload
- [ ] Tao endpoint lich su chat
- [ ] Tru credit moi cau hoi thanh cong
- [ ] Ho tro hoi document text
- [ ] Ho tro hoi transcript
- [ ] Chuan bi vector/search pipeline neu can sau nay

### AI Data Extraction

- [ ] Tao extraction type cho invoice
- [ ] Extract invoice number
- [ ] Extract invoice date
- [ ] Extract customer
- [ ] Extract total amount
- [ ] Tao extraction type cho resume
- [ ] Extract skills
- [ ] Extract experience
- [ ] Extract education
- [ ] Extract contact information
- [ ] Tao extraction type cho meeting recording
- [ ] Extract decisions
- [ ] Extract tasks
- [ ] Extract participants
- [ ] Extract deadlines
- [ ] Luu ket qua extraction dang structured JSON

API du kien:

- [ ] `POST /api/v1/ai/uploads/{id}/conversations`
- [ ] `GET /api/v1/ai/uploads/{id}/conversations`
- [ ] `POST /api/v1/conversations/{id}/messages`
- [ ] `GET /api/v1/conversations/{id}/messages`
- [ ] `POST /api/v1/ai/uploads/{id}/extract`

---

## 10. Future Sharing Features

### Password Protected Links

- [x] Them password hash cho share link
- [x] Tao endpoint verify password
- [x] Bao ve preview/download neu link co password

### Expiring Links

- [x] Them `expires_at` cho share link
- [x] Enforce expired link khi preview/download
- [x] Ho tro dat ngay het han qua `expires_at`

### Self-Destruct Files

- [ ] Them `delete_after_first_download`
- [ ] Dem download thanh cong dau tien
- [ ] Disable hoac delete file sau download dau tien

API du kien:

- [x] `PATCH /api/v1/share-links/{id}`
- [x] `DELETE /api/v1/share-links/{id}`
- [x] `POST /api/v1/share-links/{id}/regenerate`
- [x] `POST /api/v1/share-links/{code}/verify-password`

---

## 11. Analytics

- [ ] Tao migration `file_access_logs`
- [ ] Luu view events
- [ ] Luu download events
- [ ] Luu IP hash / user agent neu phu hop
- [ ] Luu last access time
- [x] Dem views
- [x] Dem downloads
- [ ] Tao endpoint analytics cho owner
- [ ] Khong expose analytics cho public user

API du kien:

- [ ] `GET /api/v1/files/{id}/analytics`

---

## 12. Folder Upload

- [ ] Thiet ke schema folder/path
- [ ] Luu relative path trong upload session
- [ ] Ho tro upload nhieu file kem folder path
- [ ] Rebuild folder tree cho frontend
- [ ] Gioi han tong dung luong upload session 300MB
- [ ] Tao endpoint list folder tree

API du kien:

- [ ] `POST /api/v1/folders/uploads`
- [ ] `GET /api/v1/folders/{id}/tree`

---

## 13. File Request Portal

- [ ] Tao migration `file_requests`
- [ ] Tao model `FileRequest`
- [ ] Tao upload portal slug, vi du `/request/client-a`
- [ ] Tao endpoint tao portal
- [ ] Tao endpoint public portal info
- [ ] Tao endpoint upload file vao portal
- [ ] Gan uploaded files voi owner portal
- [ ] Ho tro portal khong can public user login
- [ ] Ho tro bat/tat portal
- [ ] Ho tro expires_at cho portal

API du kien:

- [ ] `POST /api/v1/file-requests`
- [ ] `GET /api/v1/file-requests`
- [ ] `GET /api/v1/request/{slug}`
- [ ] `POST /api/v1/request/{slug}/files`

---

## 14. Public Profile

- [ ] Them username cho user
- [ ] Tao public profile setting
- [ ] Tao endpoint public profile theo username
- [ ] Liet ke recent public uploads
- [ ] Liet ke shared content
- [ ] Chuan bi public collections

API du kien:

- [ ] `GET /api/v1/u/{username}`

---

## 15. Personal Knowledge Base

- [ ] Tao migration `collections`
- [ ] Tao model `Collection`
- [ ] Tao migration `collection_files`
- [ ] Them file vao collection
- [ ] Xoa file khoi collection
- [ ] Liet ke collections cua user
- [ ] Liet ke files trong collection
- [ ] Search file theo filename
- [ ] Search file theo content/transcript neu co
- [ ] Chuan bi team collaboration sau nay

API du kien:

- [ ] `POST /api/v1/collections`
- [ ] `GET /api/v1/collections`
- [ ] `GET /api/v1/collections/{id}`
- [ ] `POST /api/v1/collections/{id}/files`
- [ ] `DELETE /api/v1/collections/{id}/files/{fileId}`

---

## 16. Admin and Operations

- [ ] Admin xem danh sach users
- [ ] Admin xem danh sach files
- [ ] Admin xem danh sach payments
- [ ] Admin xem danh sach AI jobs
- [ ] Admin retry AI job fail
- [ ] Admin disable share link
- [ ] Logging request ID
- [ ] Rate limiting public endpoints
- [ ] Rate limiting auth endpoints
- [ ] Rate limiting AI endpoints
- [ ] Schedule command cleanup expired links/files
- [ ] Schedule command cleanup failed temp uploads

---

## 17. OpenAPI / Swagger Documentation

- [x] Dinh nghia global OpenAPI info trong `openapi.yml`
- [x] Dinh nghia server URLs
- [x] Dinh nghia bearer auth security scheme
- [x] Dinh nghia standard error schema
- [ ] Dinh nghia pagination schema
- [ ] Dinh nghia auth schemas
- [ ] Dinh nghia file schemas
- [ ] Dinh nghia share link schemas
- [ ] Dinh nghia AI upload schemas
- [ ] Dinh nghia AI output schemas
- [ ] Dinh nghia wallet schemas
- [ ] Dinh nghia subscription schemas
- [ ] Dinh nghia payment schemas
- [ ] Dinh nghia AI job schemas
- [ ] Dinh nghia conversation schemas
- [ ] Dinh nghia analytics schemas
- [ ] Cap nhat `openapi.yml` moi khi them/sua API
- [ ] Verify Swagger UI render duoc `openapi.yml`

---

## 18. Staging / Deployment

### Vercel

- [ ] Quyet dinh Vercel dung cho frontend only hay deploy ca API serverless
- [ ] Neu frontend tach rieng, tao Vercel project cho frontend
- [ ] Neu backend Laravel deploy qua Vercel, nghien cuu serverless adapter phu hop
- [ ] Tao moi truong `staging` tren Vercel
- [ ] Cau hinh branch deploy tu `develop` hoac `staging`
- [ ] Cau hinh production deploy tu `main`
- [ ] Them environment variables cho staging
- [ ] Them environment variables cho production
- [ ] Tach database staging rieng voi production
- [ ] Tach S3 bucket / storage prefix cho staging
- [ ] Cau hinh domain staging, vi du `staging.pastelink.app`
- [ ] Cau hinh domain production
- [ ] Cau hinh build command va output directory
- [ ] Cau hinh health check endpoint cho staging
- [ ] Them GitHub Actions verify test/build truoc khi deploy
- [ ] Them deployment checklist vao docs
- [ ] Verify preview deployment cho pull request
- [ ] Verify rollback flow tren Vercel
- [ ] Cap nhat `README.md` voi link staging khi co URL chinh thuc

### Cloudflare Proxy / DNS

- [ ] Dua domain vao Cloudflare
- [ ] Cau hinh DNS records cho staging va production
- [ ] Bat Cloudflare proxy cho web traffic
- [ ] Cau hinh SSL/TLS mode phu hop, uu tien Full strict
- [ ] Cau hinh redirect HTTP -> HTTPS
- [ ] Cau hinh cache rules cho static assets
- [ ] Cau hinh bypass cache cho API endpoints
- [ ] Cau hinh security headers neu dung Cloudflare rules
- [ ] Cau hinh WAF / rate limiting cho public endpoints
- [ ] Verify domain staging qua Cloudflare proxy
- [ ] Verify domain production qua Cloudflare proxy
- [ ] Ghi lai DNS/proxy checklist vao docs

### SendGrid Email

- [ ] Tao SendGrid account / project cho PasteLink
- [ ] Verify sender identity hoac domain authentication
- [ ] Cau hinh SPF, DKIM, DMARC trong Cloudflare DNS
- [ ] Tao SendGrid API key rieng cho staging
- [ ] Tao SendGrid API key rieng cho production
- [ ] Them `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` vao `.env.example`
- [ ] Cau hinh Laravel mailer dung SendGrid SMTP hoac API
- [ ] Tao email template cho verify email
- [ ] Tao email template cho reset password
- [ ] Tao email template cho payment receipt
- [ ] Tao email template cho file/share notification neu can
- [ ] Them queue cho email sending
- [ ] Log email delivery status neu can
- [ ] Test gui email tren staging
- [ ] Verify SendGrid suppression/bounce handling

---

## 19. Suggested Milestones

### Phase 1 - MVP

- [ ] Project Foundation
- [ ] Authentication
- [ ] File Upload Core
- [ ] Public Share Links
- [ ] Basic OpenAPI docs

### Phase 2 - Monetization

- [ ] Subscription Plans
- [ ] Wallet and Credit System
- [ ] Stripe Integration
- [ ] Upload limit enforcement

### Phase 3 - AI Foundation

- [ ] AI Job Infrastructure
- [ ] AI Upload Workspace
- [ ] AI Outputs
- [ ] AI Type Detection and Routing
- [ ] AI Text Processing
- [ ] AI Document Processing
- [ ] AI Audio Processing
- [ ] AI Video Processing
- [ ] AI Summary
- [ ] AI Translation

### Phase 4 - AI Workspace

- [ ] AI Chat with Files
- [ ] AI Data Extraction
- [ ] Folder Upload
- [ ] File Request Portal

### Phase 5 - Platform Expansion

- [ ] Analytics
- [ ] Public Profile
- [ ] Personal Knowledge Base
- [ ] Team Collaboration planning
