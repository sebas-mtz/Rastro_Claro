// resources/js/Pages/Custom/Home.jsx
import React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, usePage } from "@inertiajs/react";
import Statistics from "@/Components/Statistics";

export default function Home( {
        auth,
        summary,
        speciesDistribution,
        productionByMonth,
        alerts,
    } ) {
    return (
          <>
            <Head title="Home" />
            <Statistics
                summary={summary}
                speciesDistribution={speciesDistribution}
                productionByMonth={productionByMonth}
                alerts={alerts}
            />
          </>
    );
    
}
Home.layout = (page) => (
            <AuthenticatedLayout user={page.props.auth.user}>
                {page}
            </AuthenticatedLayout>);
