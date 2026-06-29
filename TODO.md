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
- [x] Tao date-based storage path `uploads/YYYY/MM/DD`
- [ ] Rut gon filename dai hon nguong cau hinh
- [ ] Tao service xoa file vat ly khi record bi xoa
- [ ] Tao endpoint danh sach file cua user
- [ ] Tao endpoint chi tiet file
- [ ] Tao endpoint xoa file
- [ ] Viet OpenAPI schema cho file APIs
- [x] Viet test cho upload APIs

API du kien:

- [ ] `POST /api/v1/files`
- [ ] `GET /api/v1/files`
- [ ] `GET /api/v1/files/{id}`
- [ ] `DELETE /api/v1/files/{id}`

---

## 3. Public Share Links

- [ ] Tao migration `share_links`
- [ ] Tao model `ShareLink`
- [ ] Sinh public code ngan, vi du `A7X29K`
- [ ] Dam bao public code la duy nhat
- [ ] Tao public URL `/f/{code}`
- [ ] Gan moi file voi mot share link mac dinh
- [ ] Tao endpoint lay thong tin public file theo code
- [ ] Tao endpoint tai file public
- [ ] Tao endpoint copy/open link data cho frontend
- [ ] Tao endpoint QR code data hoac QR image
- [ ] Them status active/inactive cho share link
- [ ] Them soft delete neu can
- [ ] Viet OpenAPI schema cho share link APIs
- [ ] Viet test cho share link APIs

API du kien:

- [ ] `POST /api/v1/files/{id}/share-links`
- [ ] `GET /api/v1/share-links/{code}`
- [ ] `GET /api/v1/f/{code}`
- [ ] `GET /api/v1/f/{code}/download`
- [ ] `GET /api/v1/f/{code}/qr-code`

---

## 4. File Preview

### Text Preview

- [ ] Detect text file: `txt`, `md`, `json`
- [ ] Tao preview noi dung text gioi han kich thuoc
- [ ] Tao endpoint preview first section
- [ ] Tao endpoint view full content co gioi han an toan
- [ ] Format JSON preview neu file la JSON
- [ ] Xu ly encoding text co ban

### Audio Preview

- [ ] Detect audio file: `mp3`, `wav`, `m4a`
- [ ] Tra ve signed/public stream URL cho audio player
- [ ] Luu metadata audio neu co
- [ ] Tao endpoint metadata audio

### Video Preview

- [ ] Detect video file: `mp4`, `mov`, `webm`
- [ ] Tra ve signed/public stream URL cho video player
- [ ] Luu metadata video neu co
- [ ] Tao thumbnail generation job trong tuong lai

API du kien:

- [ ] `GET /api/v1/files/{id}/preview`
- [ ] `GET /api/v1/files/{id}/content`
- [ ] `GET /api/v1/f/{code}/preview`
- [ ] `GET /api/v1/f/{code}/content`

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

### AI Job Infrastructure

- [ ] Tao migration `ai_jobs`
- [ ] Tao model `AiJob`
- [ ] Tao queue job base cho AI processing
- [ ] Luu trang thai AI job: pending, processing, completed, failed
- [ ] Luu input file id
- [ ] Luu output JSON/text
- [ ] Luu error message khi fail
- [ ] Luu credit cost
- [ ] Chi cap nhat credit khi job thanh cong
- [ ] Tao endpoint xem trang thai AI job
- [ ] Tao endpoint xem ket qua AI job

### AI Transcription

- [ ] Ho tro transcription cho MP3
- [ ] Ho tro transcription cho MP4
- [ ] Goi OpenAI Speech-to-Text
- [ ] Detect English
- [ ] Detect Vietnamese
- [ ] Luu full transcript
- [ ] Chuan bi schema cho speaker identification trong tuong lai

### AI Summary

- [ ] Tao summary tu transcript
- [ ] Tao key points
- [ ] Tao action items
- [ ] Ho tro summary tu text file

### AI Translation

- [ ] Dich transcript English -> Vietnamese
- [ ] Dich transcript Vietnamese -> English
- [ ] Dich text file English -> Vietnamese
- [ ] Dich text file Vietnamese -> English

API du kien:

- [ ] `POST /api/v1/files/{id}/ai/transcribe`
- [ ] `POST /api/v1/files/{id}/ai/summarize`
- [ ] `POST /api/v1/files/{id}/ai/translate`
- [ ] `GET /api/v1/ai/jobs/{id}`
- [ ] `GET /api/v1/ai/jobs/{id}/result`

---

## 9. AI Workspace

### AI Chat with Files

- [ ] Tao migration `file_conversations`
- [ ] Tao model `FileConversation`
- [ ] Tao migration `file_messages`
- [ ] Tao model `FileMessage`
- [ ] Tao endpoint tao conversation theo file
- [ ] Tao endpoint gui cau hoi ve file
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

- [ ] `POST /api/v1/files/{id}/conversations`
- [ ] `GET /api/v1/files/{id}/conversations`
- [ ] `POST /api/v1/conversations/{id}/messages`
- [ ] `GET /api/v1/conversations/{id}/messages`
- [ ] `POST /api/v1/files/{id}/ai/extract`

---

## 10. Future Sharing Features

### Password Protected Links

- [ ] Them password hash cho share link
- [ ] Tao endpoint verify password
- [ ] Bao ve preview/download neu link co password

### Expiring Links

- [ ] Them `expires_at` cho share link
- [ ] Enforce expired link khi preview/download
- [ ] Ho tro options: 1 day, 7 days, 30 days

### Self-Destruct Files

- [ ] Them `delete_after_first_download`
- [ ] Dem download thanh cong dau tien
- [ ] Disable hoac delete file sau download dau tien

API du kien:

- [ ] `PATCH /api/v1/share-links/{id}`
- [ ] `POST /api/v1/share-links/{code}/verify-password`

---

## 11. Analytics

- [ ] Tao migration `file_access_logs`
- [ ] Luu view events
- [ ] Luu download events
- [ ] Luu IP hash / user agent neu phu hop
- [ ] Luu last access time
- [ ] Dem views
- [ ] Dem downloads
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
- [ ] Dinh nghia preview schemas
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
- [ ] Text Preview
- [ ] Audio Preview
- [ ] Video Preview
- [ ] Basic OpenAPI docs

### Phase 2 - Monetization

- [ ] Subscription Plans
- [ ] Wallet and Credit System
- [ ] Stripe Integration
- [ ] Upload limit enforcement

### Phase 3 - AI Foundation

- [ ] AI Job Infrastructure
- [ ] AI Transcription
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
