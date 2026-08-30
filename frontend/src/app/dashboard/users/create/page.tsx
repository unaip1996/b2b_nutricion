"use client";

import { UserForm } from "@/components/UserForm";

export default function CreateUserPage() {
    return (
        <div className="py-6">
            <UserForm 
                apiUrl={`${process.env.NEXT_PUBLIC_API_URL}/api/users`} 
                redirectUrl="/dashboard/users" 
                isProfile={false} 
                method="POST" 
            />
        </div>
    );
}