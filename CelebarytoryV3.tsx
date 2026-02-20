"use client";

import { motion } from "framer-motion";
import { useState, useEffect } from "react";

// ============================================================
// SVG INGREDIENTS (V3 - Flat, Vibrant, Sticker-like)
// ============================================================

const ShrimpIcon = ({ className }: { className?: string }) => (
    <svg viewBox="0 0 24 24" fill="none" className={className}>
        <path
            d="M17 7C17 7 19 4 19 2M17 7C17 7 14 5 12 6C10 7 7 10 7 14C7 18 10 21 14 21C16 21 18 20 19 18C20 16 19 13 17 12C16 11.5 15 12 14 12"
            stroke="none"
            fill="#FB923C" // Orange-400
        />
        <path
            d="M14 21C11 21 7 22 5 22M14 21C13 21 12 21 11 20"
            stroke="none"
            fill="#F97316" // Orange-500 (Shadow/Tail)
        />
        <path d="M7 14C7 12 8 9 10 8" stroke="#FDBA74" strokeWidth="2" strokeLinecap="round" />
    </svg>
);

const AvocadoIcon = ({ className }: { className?: string }) => (
    <svg viewBox="0 0 24 24" fill="none" className={className}>
        <path
            d="M12 2C8 2 5 6 5 12C5 17 8 22 12 22C16 22 19 17 19 12C19 6 16 2 12 2Z"
            fill="#4ADE80" // Green-400
        />
        <circle cx="12" cy="15" r="3.5" fill="#FACC15" /> // Yellow pit
        <path d="M12 2C15 2 19 6 19 12" stroke="#22C55E" strokeWidth="1" strokeOpacity="0.5" />
    </svg>
);

const CheeseIcon = ({ className }: { className?: string }) => (
    <svg viewBox="0 0 24 24" fill="none" className={className}>
        <path
            d="M3 12L12 4L21 12V18C21 19.1 20.1 20 19 20H5C3.9 20 3 19.1 3 18V12Z"
            fill="#FACC15" // Yellow-400
        />
        <circle cx="8" cy="14" r="1.5" fill="#FEF08A" />
        <circle cx="15" cy="16" r="2" fill="#FEF08A" />
        <circle cx="13" cy="10" r="1" fill="#FEF08A" />
    </svg>
);

const StrawberryIcon = ({ className }: { className?: string }) => (
    <svg viewBox="0 0 24 24" fill="none" className={className}>
        <path
            d="M12 20C12 20 6 14 5 10C4 6 7 4 9 4C11 4 12 6 12 6C12 6 13 4 15 4C17 4 20 6 19 10C18 14 12 20 12 20Z"
            fill="#EF4444" // Red-500
        />
        {/* Seeds */}
        <circle cx="9" cy="8" r="0.5" fill="#FECACA" />
        <circle cx="15" cy="8" r="0.5" fill="#FECACA" />
        <circle cx="12" cy="12" r="0.5" fill="#FECACA" />
        <circle cx="8" cy="14" r="0.5" fill="#FECACA" />
        <circle cx="16" cy="14" r="0.5" fill="#FECACA" />
        <circle cx="12" cy="16" r="0.5" fill="#FECACA" />
        {/* Leaves */}
        <path d="M9 4L12 2L15 4" stroke="#22C55E" strokeWidth="2" strokeLinecap="round" />
    </svg>
);

const BlueberryIcon = ({ className }: { className?: string }) => (
    <svg viewBox="0 0 24 24" fill="none" className={className}>
        <circle cx="12" cy="12" r="8" fill="#60A5FA" /> // Blue-400
        <circle cx="15" cy="9" r="2" fill="#93C5FD" /> // Highlight
    </svg>
);

const EggIcon = ({ className }: { className?: string }) => (
    <svg viewBox="0 0 24 24" fill="none" className={className}>
        <path
            d="M12 3C8 3 5 7 5 13C5 17 8 20 12 20C16 20 19 17 19 13C19 7 16 3 12 3Z"
            fill="white"
        />
        <circle cx="12" cy="13" r="3" fill="#FACC15" />
    </svg>
);

const INGREDIENTS = [
    { id: 1, Icon: ShrimpIcon, weight: "medium" },
    { id: 2, Icon: AvocadoIcon, weight: "heavy" },
    { id: 3, Icon: CheeseIcon, weight: "medium" },
    { id: 4, Icon: StrawberryIcon, weight: "heavy" },
    { id: 5, Icon: BlueberryIcon, weight: "light" },
    { id: 6, Icon: EggIcon, weight: "medium" },
];

// ============================================================
// MAIN COMPONENT
// ============================================================

export default function CelebarytoryV3() {
    const [stage, setStage] = useState<"idle" | "burst">("idle");

    useEffect(() => {
        // Start animation on mount
        const t1 = setTimeout(() => setStage("burst"), 500);
        return () => clearTimeout(t1);
    }, []);

    // Generate 50 confetti pieces with varied properties
    const confetti = Array.from({ length: 50 }).map((_, i) => {
        const type = INGREDIENTS[i % INGREDIENTS.length];

        // Random physics based on "weight"
        const spreadX = (Math.random() - 0.5) * 350;
        const spreadY = (Math.random() - 1) * 500 - 150; // Upwards burst

        return {
            id: i,
            Component: type.Icon,
            weight: type.weight,
            dx: spreadX,
            dy: spreadY,
            rot: Math.random() * 720 - 360,
            scale: 0.6 + Math.random() * 0.8,
            delay: Math.random() * 0.15, // Tight burst
        };
    });

    return (
        <div className="flex flex-col items-center gap-4">
            {/* Phone Mockup */}
            <div
                className="relative overflow-hidden rounded-[3rem] border-[6px] border-zinc-900 bg-[#09090b] shadow-2xl"
                style={{ width: 300, height: 620 }}
            >
                {/* Dynamic Island */}
                <div className="absolute top-2 left-1/2 z-50 h-[26px] w-[100px] -translate-x-1/2 rounded-full bg-black border border-white/5" />

                {/* Status Bar */}
                <div className="absolute top-2 left-0 right-0 z-40 flex items-center justify-between px-8 pt-1 opacity-60">
                    <span className="text-[10px] font-semibold text-white">9:41</span>
                    <div className="flex items-center gap-1">
                        <div className="w-4 h-2.5 rounded-[1px] border border-white" />
                    </div>
                </div>

                {/* --- MAIN ANIMATION AREA --- */}
                <div className="absolute inset-0 flex flex-col items-center justify-center p-6 text-white overflow-hidden">

                    {/* Confetti Layer */}
                    <div className="absolute inset-0 z-10 pointer-events-none">
                        {confetti.map((c) => (
                            <motion.div
                                key={c.id}
                                className="absolute left-1/2 top-1/2 w-8 h-8 -ml-4 -mt-4"
                                initial={{ x: 0, y: 0, scale: 0, opacity: 1, rotate: 0 }}
                                animate={stage === "burst" ? {
                                    x: c.dx,
                                    y: [0, c.dy, 800], // Start -> Up -> Fall off screen
                                    rotate: c.rot,
                                    scale: c.scale,
                                    opacity: [1, 1, 1, 0] // Fade out at very end
                                } : {}}
                                transition={{
                                    // Custom mixing of easing for organic feel
                                    duration: c.weight === "light" ? 5 : c.weight === "heavy" ? 3 : 4,
                                    times: [0, 0.15, 1], // Fast burst, slow fall
                                    ease: [0.2, 0.8, 0.2, 1],
                                    delay: c.delay
                                }}
                            >
                                <c.Component className="w-full h-full drop-shadow-md" />
                            </motion.div>
                        ))}
                    </div>

                    {/* Central Character */}
                    <motion.div
                        className="relative z-20 mb-8"
                        initial={{ scale: 0, rotate: -180 }}
                        animate={stage === "burst" ? {
                            scale: [0, 1.2, 1],
                            rotate: [0, 10, -10, 0]
                        } : {}}
                        transition={{
                            scale: { duration: 0.5, ease: "easeOut", delay: 0.1 }, // FIXED: easeOut used primarily
                            rotate: { duration: 0.5, delay: 0.3 }
                        }}
                    >
                        {/* New Mascot V3 - "Chef Hat" */}
                        <svg width="100" height="100" viewBox="0 0 100 100" fill="none">
                            {/* Hat */}
                            <path
                                d="M20 60 C 20 40, 30 10, 50 10 C 70 10, 80 40, 80 60 L 80 75 L 20 75 Z"
                                fill="white"
                            />
                            <rect x="20" y="75" width="60" height="10" rx="2" fill="#E4E4E7" />

                            {/* Face */}
                            <circle cx="40" cy="50" r="4" fill="#18181B" />
                            <circle cx="60" cy="50" r="4" fill="#18181B" />
                            <path d="M45 58 Q 50 62, 55 58" stroke="#18181B" strokeWidth="2" strokeLinecap="round" />

                            {/* Decoration */}
                            <path d="M30 30 Q 50 15, 70 30" stroke="#E4E4E7" strokeWidth="2" fill="none" />
                        </svg>
                    </motion.div>

                    {/* Text Content */}
                    <motion.div
                        className="text-center z-30"
                        initial={{ opacity: 0, y: 20 }}
                        animate={stage === "burst" ? { opacity: 1, y: 0 } : {}}
                        transition={{ delay: 0.4, duration: 0.6 }}
                    >
                        <h2 className="text-3xl font-extrabold mb-2 bg-gradient-to-r from-orange-400 to-red-500 bg-clip-text text-transparent">
                            Yummmm!
                        </h2>
                        <p className="text-sm text-zinc-400">Meal prepped & ready.</p>
                    </motion.div>

                    {/* Button */}
                    <motion.button
                        className="mt-10 w-full max-w-[220px] bg-white text-black font-bold py-3.5 rounded-2xl shadow-xl active:scale-95 transition-transform z-30"
                        initial={{ opacity: 0, y: 40 }}
                        animate={stage === "burst" ? { opacity: 1, y: 0 } : {}}
                        transition={{ type: "spring", delay: 0.6 }}
                        onClick={() => {
                            setStage("idle");
                            setTimeout(() => setStage("burst"), 100);
                        }}
                    >
                        View Recipe
                    </motion.button>

                </div>

                {/* Home Indicator */}
                <div className="absolute bottom-2 left-1/2 h-[4px] w-[100px] -translate-x-1/2 rounded-full bg-white/10 z-40" />
            </div>

            <p className="text-xs text-zinc-500">Tap button to replay</p>
        </div>
    );
}