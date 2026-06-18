"use client";

import { UserForm } from "@/components/UserForm";

export default function ProfilePage() {
    return (
        <UserForm 
            apiUrl="http://localhost:8000/api/profile"
            redirectUrl="/dashboard"
            isProfile={true}
            method="PUT"
        />
    );
}