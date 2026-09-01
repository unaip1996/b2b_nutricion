"use client";

import { UserForm } from "@/components/UserForm";

export default function ProfilePage() {
    return (
        <UserForm 
            apiUrl="/api/profile" 
            redirectUrl="/dashboard/rag" 
            isProfile={true} 
            method="PUT" 
        />
    );
}