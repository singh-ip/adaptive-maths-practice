import { motion } from 'motion/react';

const mathSymbols = ['×', '÷', '+', '−', '=', '∑', '∏', '√'];

interface MathLoaderProps {
  message?: string;
}

export const MathLoader = ({ message = 'Checking your answer...' }: MathLoaderProps) => {
  return (
    <div className="flex flex-col items-center justify-center gap-6 py-8">
      <div className="relative w-32 h-32">
        {mathSymbols.map((symbol, index) => (
          <motion.div
            key={index}
            className="absolute text-4xl text-blue-600 dark:text-blue-400 opacity-60"
            initial={{
              x: 0,
              y: 0,
              opacity: 0,
            }}
            animate={{
              x: [0, Math.cos(index * Math.PI / 4) * 40, 0],
              y: [0, Math.sin(index * Math.PI / 4) * 40, 0],
              opacity: [0, 0.6, 0],
              rotate: [0, 360, 720],
            }}
            transition={{
              duration: 2.5,
              repeat: Infinity,
              delay: index * 0.2,
              ease: "easeInOut",
            }}
            style={{
              left: '50%',
              top: '50%',
              translateX: '-50%',
              translateY: '-50%',
            }}
          >
            {symbol}
          </motion.div>
        ))}
        
        {/* Central pulsing indicator */}
        <motion.div
          className="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-12 h-12 bg-blue-600 dark:bg-blue-500 rounded-full"
          animate={{
            scale: [1, 1.2, 1],
            opacity: [0.5, 0.8, 0.5],
          }}
          transition={{
            duration: 1.5,
            repeat: Infinity,
            ease: "easeInOut",
          }}
        />
      </div>

      {/* Loading message */}
      <motion.p
        className="text-lg text-gray-700 dark:text-gray-300"
        animate={{
          opacity: [0.5, 1, 0.5],
        }}
        transition={{
          duration: 2,
          repeat: Infinity,
          ease: "easeInOut",
        }}
      >
        {message}
      </motion.p>
    </div>
  );
};
