# PasteLink

> Paste Anything. Get a Link.

PasteLink is a modern file sharing and AI-powered content processing platform.

Users can instantly upload files by pasting, dragging, or selecting them from their computer. Each uploaded file receives a shareable public link, allowing content to be distributed quickly and securely.

Beyond file sharing, PasteLink provides AI-powered tools such as transcription, summarization, translation, content extraction, and file-based conversations.

The goal of PasteLink is simple:

```text
Upload Anything
-> Get a Shareable Link
-> Unlock Insights with AI
```

---

## Vision

Most file sharing services stop at uploading and downloading.

PasteLink aims to become a platform where users can:

- Upload files instantly
- Share files securely
- Send files to a separate AI workspace when analysis is needed
- Process files using AI
- Monetize advanced AI features through subscriptions and credits
- Build a personal knowledge workspace around uploaded content

---

## Core Features

### File Upload

Users can upload files through:

- File Picker
- Drag and Drop
- Clipboard Paste (Ctrl+C -> Ctrl+V)

Supported:

- Single file upload
- Multiple file upload
- Folder upload (future)

Upload limit:

- Maximum 300MB per upload session

---

### Smart File Storage

Files are stored using a date-based structure.

Example:

```text
https://pastelink.app/uploads/2026/06/12/document.pdf
```

Long filenames are automatically shortened.

Example:

```text
very-long-document-name-that-exceeds-fifty-characters.pdf

-> very-long-document-name-that-exceeds-fifty-char....pdf
```

---

### Public Share Links

Every uploaded file receives a shareable public URL.

Example:

```text
https://pastelink.app/f/A7X29K
```

Features:

- Copy Link
- Open Link
- Share Link
- Generate QR Code

---

### GetLink File Delivery

The upload and share-link flow stays focused on:

- Upload
- Copy Link
- Open Link
- QR Code
- Download

AI analysis is handled by the separate AI workspace. Users can upload directly in AI or send an existing uploaded file to AI.

---

## AI Features

### AI Upload Workspace

Users upload files into the AI tab for analysis. AI uploads do not automatically create public share links.

Supported file groups:

- Text: txt, md, json
- Documents: pdf, docx
- Audio: mp3, wav, m4a
- Video: mp4, mov, webm

Stored data:

- Original file metadata
- Detected file kind
- Processing status
- Extracted metadata such as duration, dimensions, page count, encoding, or word count
- Generated outputs such as transcript, summary, translation, extraction, thumbnail, subtitles, or chapters

---

### AI Text Processing

Supported:

- txt
- md
- json

Features:

- Preview first section
- Safe full content view
- JSON formatting
- Basic encoding handling
- Summary
- Translation

---

### AI Document Processing

Supported:

- PDF
- DOCX

Features:

- Text extraction
- Page metadata
- Summary
- Key points
- Action items

---

### AI Audio Processing

Supported:

- mp3
- wav
- m4a

Features:

- Speech-to-Text
- English Detection
- Vietnamese Detection
- Timestamped transcript when supported
- Summary
- Key Points
- Action Items

Output:

- Full Transcript
- Speaker Identification (future)

---

### AI Video Processing

Supported:

- mp4
- mov
- webm

Features:

- Audio extraction
- Speech-to-Text
- Thumbnail frame generation
- Transcript
- Summary
- Chapters or timeline when timestamps are available
- Action Items

---

### AI Summary

Generate:

- Summary
- Key Points
- Action Items

Workflow:

```text
Meeting Recording
-> Transcript
-> Summary
-> Action Items
```

---

### AI Translation

Supported:

- Transcript Translation
- Text File Translation

Examples:

```text
English -> Vietnamese
Vietnamese -> English
```

---

### AI Chat with Files

Users can interact with uploaded files.

Examples:

```text
Summarize this document

What are the main requirements?

List all APIs mentioned in this file.

What decisions were made during this meeting?
```

---

### AI Data Extraction

Examples:

#### Invoice

Extract:

- Invoice Number
- Date
- Customer
- Total Amount

#### Resume

Extract:

- Skills
- Experience
- Education
- Contact Information

#### Meeting Recording

Extract:

- Decisions
- Tasks
- Participants
- Deadlines

---

## Wallet and Credit System

PasteLink uses a hybrid monetization model:

- Subscription Plans
- Credit Wallet

Users can top up credits and spend them on premium AI features.

---

### Credit Wallet

Each user owns a wallet.

Example:

```text
Balance: 1,500 Credits
```

---

### Top-Up Packages

| Package | Credits |
| ------- | ------: |
| $5      |     500 |
| $10     |   1,100 |
| $20     |   2,400 |
| $50     |   6,500 |

Bonus credits are included in larger packages.

---

### Credit Usage

| Feature            | Credits |
| ------------------ | ------: |
| AI Transcript      |     300 |
| AI Summary         |     100 |
| AI Translation     |     100 |
| AI Chat with File  |      50 |
| AI Data Extraction |     150 |

Credits are deducted only after successful processing.

---

## Subscription Plans

### Free Plan

- 10 uploads total
- Basic file sharing
- Basic preview support

### Daily Plan

- $2/day
- 30 uploads per day

### Weekly Plan

- $8/week
- 30 uploads per day

### Monthly Plan

- $20/month
- 30 uploads per day

---

## Future Features

### Password Protected Links

Example:

```text
Link Password: 123456
```

---

### Expiring Links

Options:

- 1 Day
- 7 Days
- 30 Days

---

### Self-Destruct Files

Example:

```text
Delete After First Download
```

---

### Download Analytics

Track:

- Views
- Downloads
- Last Access Time

---

### Folder Upload

Upload complete folder structures.

Example:

```text
Photos/
|-- image1.jpg
|-- image2.jpg
`-- image3.jpg
```

---

### File Request Portal

Create upload portals without requiring user registration.

Example:

```text
https://pastelink.app/request/client-a
```

Perfect for:

- Clients
- Recruiters
- Designers
- Accountants
- QA Teams

---

### Public Profile

Example:

```text
https://pastelink.app/u/username
```

Features:

- Recent Uploads
- Shared Content
- Public Collections

---

### Personal Knowledge Base

Users can organize uploaded content into searchable collections.

Example:

```text
Projects
Meetings
Invoices
Learning Materials
```

---

## Tech Stack

### Backend

- Laravel
- Laravel Sanctum
- MySQL
- Redis
- Horizon

### Frontend

- React.js
- Vite
- TanStack Query

### Infrastructure

- Docker
- S3 Compatible Storage
- Cloudflare CDN

### Payment

- Stripe

### AI

- OpenAI Speech-to-Text
- OpenAI GPT Models

---

## Project Structure

```text
pastelink/
|-- backend/
|-- frontend/
|-- docker/
|-- docs/
`-- README.md
```

---

## Roadmap

### Phase 1 - MVP

- Authentication
- File Upload
- Clipboard Upload
- Drag and Drop Upload
- Public Share Links

### Phase 2 - Monetization

- Subscription System
- Wallet System
- Credit Transactions
- Stripe Integration

### Phase 3 - AI Foundation

- AI Upload Workspace
- AI Text Processing
- AI Document Processing
- AI Audio Processing
- AI Video Processing
- AI Summary
- AI Translation

### Phase 4 - AI Workspace

- AI Chat with Files
- AI Data Extraction
- Folder Upload
- File Request Portal

### Phase 5 - Platform Expansion

- Analytics
- Public Profiles
- Knowledge Base
- Team Collaboration

---

## Project Description

PasteLink helps users upload files instantly, generate shareable links, and use a separate AI workspace to unlock insights from documents, audio, and video.

---

## License

Private Project

All rights reserved.
