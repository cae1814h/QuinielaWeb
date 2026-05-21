import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { CheckCircle2, Trophy } from "lucide-react";
import { TooltipProvider } from "@/components/ui/tooltip";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent } from "@/components/ui/card";
import { cn } from "@/lib/utils";

const queryClient = new QueryClient();

function PointsBadge({ points }: { points: number }) {
  const cfg =
    points === 3
      ? { color: "#00d896", bg: "rgba(0,216,150,0.16)", border: "rgba(0,216,150,0.28)", label: "+3 puntos" }
      : points === 1
        ? { color: "#fbbf24", bg: "rgba(251,191,36,0.16)", border: "rgba(251,191,36,0.28)", label: "+1 punto" }
        : { color: "#ef4444", bg: "rgba(239,68,68,0.16)", border: "rgba(239,68,68,0.28)", label: "0 puntos" };

  return (
    <span
      className="inline-flex items-center justify-center rounded-full px-3 py-1 text-sm font-extrabold"
      style={{ color: cfg.color, backgroundColor: cfg.bg, border: `1px solid ${cfg.border}` }}
    >
      {cfg.label}
    </span>
  );
}

function Home() {
  const playerName = "Cristian A.";
  const points = 3;

  return (
    <div className="min-h-screen w-full bg-[#07111f] text-slate-100 flex items-center justify-center p-6">
      <Card className="w-full max-w-md border border-emerald-500/20 bg-[#0b1628] shadow-2xl">
        <CardContent className="p-6 text-center space-y-5">
          <div className="flex flex-col items-center gap-3">
            <div className="h-12 w-12 rounded-full border border-red-400/40 bg-red-500/10 flex items-center justify-center text-red-400">
              <CheckCircle2 className="h-6 w-6" />
            </div>
            <div className="text-xl font-extrabold">Partido finalizado</div>
          </div>

          <div className="space-y-2">
            <div className="text-sm text-slate-400">Mi pronóstico</div>
            <div className="mx-auto w-fit rounded-2xl border border-emerald-500/25 bg-emerald-500/10 px-6 py-4 shadow-inner">
              <div className="flex flex-col items-center gap-2">
                <Trophy className="h-5 w-5 text-red-400" />
                <div className="text-3xl font-extrabold text-red-400 leading-none">2 - 2</div>
                <PointsBadge points={points} />
              </div>
            </div>
          </div>

          <div className="rounded-2xl border border-emerald-500/20 bg-[#0e1c30] px-5 py-4 text-sm leading-6 text-red-300 shadow-inner">
            ¡Felicidades {playerName}! Acertaste el ganador y marcador de este partido, ganaste {points} puntos.
          </div>

          <div className="flex justify-center gap-2 pt-2">
            <Badge variant="outline" className={cn("border-red-400/30 text-red-300")}>Finalizado</Badge>
            <Badge variant="outline" className="border-emerald-500/30 text-emerald-300">+{points} pts</Badge>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <TooltipProvider>
        <Home />
      </TooltipProvider>
    </QueryClientProvider>
  );
}

export default App;
