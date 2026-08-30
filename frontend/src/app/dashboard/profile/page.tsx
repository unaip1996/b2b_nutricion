"use client";

import { UserForm } from "@/components/UserForm";

export default function ProfilePage() {
    return (
        <UserForm 
            apiUrl="${process.env.NEXT_PUBLIC_API_URL}/api/profile" 
            redirectUrl="/dashboard/rag" 
            isProfile={true} 
            method="PUT" 
        />
    );
}