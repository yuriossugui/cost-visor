"use client";

import * as React from "react";
import { cn } from "@/lib/utils";
import { ChevronLeft, ChevronRight } from "lucide-react";

const Pagination = ({
  className,
  ...props
}: React.ComponentProps<"nav">) => {
  return (
    <nav
      role="navigation"
      aria-label="Pagination"
      className={cn("flex items-center justify-center", className)}
      {...props}
    />
  );
};
Pagination.displayName = "Pagination";

const PaginationContent = React.forwardRef<
  HTMLUListElement,
  React.ComponentProps<"ul">
>(({ className, ...props }, ref) => (
  <ul
    ref={ref}
    className={cn("flex items-center space-x-1", className)}
    {...props}
  />
));
PaginationContent.displayName = "PaginationContent";

const PaginationItem = React.forwardRef<
  HTMLLIElement,
  React.ComponentProps<"li">
>(({ className, ...props }, ref) => (
  <li
    ref={ref}
    className={cn("", className)}
    {...props}
  />
));
PaginationItem.displayName = "PaginationItem";

const PaginationLink = ({
  className,
  isActive,
  onClick,
  disabled,
  ...props
}: React.ComponentProps<"a"> & {
  isActive?: boolean;
  disabled?: boolean;
  onClick?: (e: React.MouseEvent<HTMLAnchorElement>) => void;
}) => (
  <a
    href={props.href}
    onClick={(e) => {
      if (disabled) e.preventDefault();
      onClick?.(e as React.MouseEvent<HTMLAnchorElement>);
    }}
    className={cn(
      "flex h-9 w-9 items-center justify-center font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none",
      isActive
        ? "bg-primary text-primary-foreground"
        : "hover:bg-accent hover:text-accent-foreground",
      className
    )}
    aria-current={isActive ? "page" : undefined}
    aria-disabled={disabled}
    {...props}
  />
);
PaginationLink.displayName = "PaginationLink";

const PaginationPrevious = ({
  className,
  onClick,
  disabled,
  ...props
}: React.ComponentProps<"a"> & {
  onClick?: (e: React.MouseEvent<HTMLAnchorElement>) => void;
  disabled?: boolean;
}) => (
  <a
    href={props.href}
    onClick={(e) => {
      if (disabled) e.preventDefault();
      onClick?.(e as React.MouseEvent<HTMLAnchorElement>);
    }}
    className={cn(
      "flex h-9 w-9 items-center justify-center gap-1.5 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none",
      className
    )}
    aria-disabled={disabled}
    {...props}
  >
    <ChevronLeft className="h-4 w-4" />
    <span>Anterior</span>
  </a>
);
PaginationPrevious.displayName = "PaginationPrevious";

const PaginationNext = ({
  className,
  onClick,
  disabled,
  ...props
}: React.ComponentProps<"a"> & {
  onClick?: (e: React.MouseEvent<HTMLAnchorElement>) => void;
  disabled?: boolean;
}) => (
  <a
    href={props.href}
    onClick={(e) => {
      if (disabled) e.preventDefault();
      onClick?.(e as React.MouseEvent<HTMLAnchorElement>);
    }}
    className={cn(
      "flex h-9 w-9 items-center justify-center gap-1.5 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none",
      className
    )}
    aria-disabled={disabled}
    {...props}
  >
    <span>Próximo</span>
    <ChevronRight className="h-4 w-4" />
  </a>
);
PaginationNext.displayName = "PaginationNext";

const PaginationEllipsis = ({
  className,
  ...props
}: React.ComponentProps<"span">) => (
  <span
    role="separator"
    aria-hidden="true"
    className={cn("flex h-9 w-9 items-center justify-center text-muted-foreground", className)}
    {...props}
  >
    <span>...</span>
  </span>
);
PaginationEllipsis.displayName = "PaginationEllipsis";

export {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationLink,
  PaginationPrevious,
  PaginationNext,
  PaginationEllipsis,
};