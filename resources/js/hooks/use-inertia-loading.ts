import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';

export function useInertiaLoading() {
    const [loading, setLoading] = useState<boolean>(false);

    useEffect(() => {
        const onStart = () => setLoading(true);
        const onFinish = () => setLoading(false);
        router.on('start', onStart);
        router.on('finish', onFinish);
        return () => {
            // No official detach API; leave listeners attached (short-lived pages)
        };
    }, []);

    return loading;
}


