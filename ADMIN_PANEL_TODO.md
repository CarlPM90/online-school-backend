# Admin Panel Setup - TODO

## Issue
User previously had access to an admin panel when deployed on Google Cloud Console, but can't find it in current Railway deployment.

## Root Cause
The admin panel is a **separate frontend application** that needs to be deployed alongside the backend API.

## Solution Found
The EscolaLMS admin panel is available as a separate React application:

- **Repository**: https://github.com/EscolaLMS/Admin
- **Description**: Admin SPA Panel for API
- **Technology**: React/TypeScript with Ant Design
- **Documentation**: Available at docs.wellms.io

## Current Backend Admin Credentials
- **Email**: `admin@escolalms.com`
- **Password**: `secret`
- **API Login**: `POST /api/auth/login`

## Next Steps (When Ready)
1. Clone the admin panel repository: `git clone https://github.com/EscolaLMS/Admin.git`
2. Configure it to connect to the current backend API: `https://web-production-82cf.up.railway.app`
3. Deploy it to Vercel or Railway
4. Set environment variables to point to the backend API

## Current API Documentation
Available at: https://web-production-82cf.up.railway.app/api/documentation

## Notes
- The backend API is working correctly
- All admin endpoints are available via API
- Just need the admin frontend interface to manage content easily